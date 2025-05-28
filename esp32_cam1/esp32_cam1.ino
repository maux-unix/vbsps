/* INCLUDES ================================================================ */
#include <kepstun-bismillah-bisa_inferencing.h>
#include "edge-impulse-sdk/dsp/image/image.hpp"

#include <WiFi.h>
#include <HTTPClient.h>
#include <algorithm>
#include <cstring>

#include "esp_camera.h"
#include "secrets.h"
#include "esp32def.h"

/* GLOBAL VARIABLES ======================================================== */
static bool debug_nn = false;
static bool is_initialised = false;
uint8_t *snapshot_buf;

bool slot_values[16];
uint8_t packed[2] = { 0 };
uint32_t checksum = 0;
size_t count = 0;


static camera_config_t camera_config = {
  .pin_pwdn = PWDN_GPIO_NUM,
  .pin_reset = RESET_GPIO_NUM,
  .pin_xclk = XCLK_GPIO_NUM,
  .pin_sscb_sda = SIOD_GPIO_NUM,
  .pin_sscb_scl = SIOC_GPIO_NUM,

  .pin_d7 = Y9_GPIO_NUM,
  .pin_d6 = Y8_GPIO_NUM,
  .pin_d5 = Y7_GPIO_NUM,
  .pin_d4 = Y6_GPIO_NUM,
  .pin_d3 = Y5_GPIO_NUM,
  .pin_d2 = Y4_GPIO_NUM,
  .pin_d1 = Y3_GPIO_NUM,
  .pin_d0 = Y2_GPIO_NUM,
  .pin_vsync = VSYNC_GPIO_NUM,
  .pin_href = HREF_GPIO_NUM,
  .pin_pclk = PCLK_GPIO_NUM,

  //XCLK 20MHz or 10MHz for OV2640 double FPS (Experimental)
  .xclk_freq_hz = 20000000,
  .ledc_timer = LEDC_TIMER_0,
  .ledc_channel = LEDC_CHANNEL_0,

  .pixel_format = PIXFORMAT_JPEG,  //YUV422,GRAYSCALE,RGB565,JPEG
  .frame_size = FRAMESIZE_QVGA,    //QQVGA-UXGA Do not use sizes above QVGA when not JPEG

  .jpeg_quality = 12,  //0-63 lower number means higher quality
  .fb_count = 1,       //if more than one, i2s runs in continuous mode. Use only with JPEG
  .fb_location = CAMERA_FB_IN_PSRAM,
  .grab_mode = CAMERA_GRAB_WHEN_EMPTY,
};

/* Function definitions ------------------------------------------------------- */
bool ei_camera_init(void);
void ei_camera_deinit(void);
bool ei_camera_capture(uint32_t img_width, uint32_t img_height, uint8_t *out_buf);

void send_data(void);
void connect_wifi(void);
uint32_t crc32_castagnoli_encode(const uint8_t *data, size_t length);
void pack_bools_to_bytes(const bool *bools, uint8_t *output, size_t length);
void randomize_slot_values(void);

/**
* @brief      Arduino setup function
*/
void setup(void) {
  Serial.begin(115200);
  while (!Serial)
    ;
  Serial.println("Edge Impulse Inferencing Demo");

  if (ei_camera_init() == false) {
    ei_printf("Failed to initialize Camera!\r\n");
  } else {
    ei_printf("Camera initialized\r\n");
  }

  connect_wifi();
  ei_printf("\nStarting continious inference in 2 seconds...\n");
  ei_sleep(2000);
}

/**
* @brief      Get data and run inferencing
*
* @param[in]  debug  Get debug info if true
*/
void loop() {
  if (ei_sleep(5) != EI_IMPULSE_OK) return;

  snapshot_buf = (uint8_t *)malloc(EI_CAMERA_RAW_FRAME_BUFFER_COLS * EI_CAMERA_RAW_FRAME_BUFFER_ROWS * EI_CAMERA_FRAME_BYTE_SIZE);

  if (snapshot_buf == nullptr) {
    ei_printf("ERR: Failed to allocate snapshot buffer!\n");
    return;
  }

  ei::signal_t signal;
  signal.total_length = EI_CLASSIFIER_INPUT_WIDTH * EI_CLASSIFIER_INPUT_HEIGHT;
  signal.get_data = &ei_camera_get_data;

  if (ei_camera_capture((size_t)EI_CLASSIFIER_INPUT_WIDTH, (size_t)EI_CLASSIFIER_INPUT_HEIGHT, snapshot_buf) == false) {
    ei_printf("Failed to capture image\r\n");
    free(snapshot_buf);
    return;
  }

  // Run the classifier
  ei_impulse_result_t result = { 0 };

  EI_IMPULSE_ERROR err = run_classifier(&signal, &result, debug_nn);
  if (err != EI_IMPULSE_OK) {
    ei_printf("ERR: Failed to run classifier (%d)\n", err);
    return;
  }

  // print the predictions
  ei_printf("Predictions (DSP: %d ms., Classification: %d ms., Anomaly: %d ms.): \n",
            result.timing.dsp, result.timing.classification, result.timing.anomaly);


#if EI_CLASSIFIER_OBJECT_DETECTION == 1
  // 1) Kumpulkan semua bounding boxes
  std::vector<ei_impulse_result_bounding_box_t> boxes;
  for (uint32_t i = 0; i < result.bounding_boxes_count; i++) {
    auto &bb = result.bounding_boxes[i];
    if (bb.value > 0) boxes.push_back(bb);
  }

  if (boxes.empty()) {
    send_data();
    return;
  }

  // 2) Cari min/max X dan Y
  uint32_t minX = boxes[0].x, maxX = boxes[0].x;
  uint32_t minY = boxes[0].y, maxY = boxes[0].y;
  for (auto &bb : boxes) {
    minX = std::min(minX, bb.x);
    maxX = std::max(maxX, bb.x);
    minY = std::min(minY, bb.y);
    maxY = std::max(maxY, bb.y);
  }

  float colWidth = (maxX - minX) / 8.0f;   
  float rowHeight = (maxY - minY) / 2.0f;  
  if (colWidth <= 0) colWidth = 1.0f;
  if (rowHeight <= 0) rowHeight = 1.0f;


  //3) sorting by y then x
  std::sort(boxes.begin(), boxes.end(),
      [](const ei_impulse_result_bounding_box_t &a, const ei_impulse_result_bounding_box_t &b) {
          if (a. x!= b.x) return a.x < b.x;
          return a.y < b.y;
      }
  );

  // 4) Reset slotValues
  std::memset(slot_values, 0, sizeof(slot_values));

  
  // 5) Partition into two rows by y-threshold (60)
  std::vector<ei_impulse_result_bounding_box_t> row0, row1;
  for (auto &bb : boxes) {
    if (bb.y < 60) {
      bb.y = 40;
      row0.push_back(bb);
    }   // baris atas → slots 0..7
    else {
      bb.y = 80;
      row1.push_back(bb);   // baris bawah → slots 8..15
    }
  }

  // 6) Assign slot_values in X-order within each row
  for (size_t i = 0; i < row0.size() && i < 8; ++i) { //
    if (row0[i].label[0] == 'f') slot_values[i] = true;
    ei_printf("Slot %2d: x=%3d, y=%3d, label=%s\n",
      int(i), int(row0[i].x), int(row0[i].y), row0[i].label);
  }
  for (size_t i = 0; i < row1.size() && i < 8; ++i) {
    if (row1[i].label[0] == 'f') slot_values[8 + i] = true;
    ei_printf("Slot %2d: x=%3d, y=%3d, label=%s\n",
      int(8 + i), int(row1[i].x), int(row1[i].y), row1[i].label);
  }

  // Print the prediction results (classification)
#else
  ei_printf("Predictions:\r\n");
  for (uint16_t i = 0; i < EI_CLASSIFIER_LABEL_COUNT; i++) {
    ei_printf("  %s: ", ei_classifier_inferencing_categories[i]);
    ei_printf("%.5f\r\n", result.classification[i].value);
  }
#endif

  // Print anomaly result (if it exists)
#if EI_CLASSIFIER_HAS_ANOMALY
  ei_printf("Anomaly prediction: %.3f\r\n", result.anomaly);
#endif

#if EI_CLASSIFIER_HAS_VISUAL_ANOMALY
  ei_printf("Visual anomalies:\r\n");
  for (uint32_t i = 0; i < result.visual_ad_count; i++) {
    ei_impulse_result_bounding_box_t bb = result.visual_ad_grid_cells[i];
    if (bb.value == 0) {
      continue;
    }
    ei_printf("  %s (%f) [ x: %u, y: %u, width: %u, height: %u ]\r\n",
              bb.label,
              bb.value,
              bb.x,
              bb.y,
              bb.width,
              bb.height);
  }
#endif


  // TAMBAHAN
  pack_bools_to_bytes(slot_values, packed, 16);
  checksum = crc32_castagnoli_encode(packed, 2);

  char slot_values_str[17];  // 16 chars + null terminator
  for (int i = 0; i < 16; ++i) {
    slot_values_str[i] = slot_values[i] ? '1' : '0';
  }
  slot_values_str[16] = '\0';  // Null terminate string

  char buffer[80];
  sprintf(buffer, "%zu, %s, %02X %02X, %08X", count, slot_values_str, packed[0], packed[1], checksum);
  Serial.println(buffer);

  delay(500);
  send_data();


  free(snapshot_buf);
}


/**
 * @brief   Setup image sensor & start streaming
 *
 * @retval  false if initialisation failed
 */
bool ei_camera_init(void) {

  if (is_initialised) return true;

#if defined(CAMERA_MODEL_ESP_EYE)
  pinMode(13, INPUT_PULLUP);
  pinMode(14, INPUT_PULLUP);
#endif

  //initialize the camera
  esp_err_t err = esp_camera_init(&camera_config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x\n", err);
    return false;
  }

  sensor_t *s = esp_camera_sensor_get();
  // initial sensors are flipped vertically and colors are a bit saturated
  if (s->id.PID == OV3660_PID) {
    s->set_vflip(s, 1);       // flip it back
    s->set_brightness(s, 1);  // up the brightness just a bit
    s->set_saturation(s, 0);  // lower the saturation
  }

#if defined(CAMERA_MODEL_M5STACK_WIDE)
  s->set_vflip(s, 1);
  s->set_hmirror(s, 1);
#elif defined(CAMERA_MODEL_ESP_EYE)
  s->set_vflip(s, 1);
  s->set_hmirror(s, 1);
  s->set_awb_gain(s, 1);
// #elif defined(CAMERA_MODEL_AI_THINKER)
//   s->set_vflip(s, 1);
#endif

  is_initialised = true;
  return true;
}

/**
 * @brief      Stop streaming of sensor data
 */
void ei_camera_deinit(void) {

  //deinitialize the camera
  esp_err_t err = esp_camera_deinit();

  if (err != ESP_OK) {
    ei_printf("Camera deinit failed\n");
    return;
  }

  is_initialised = false;
  return;
}


/**
 * @brief      Capture, rescale and crop image
 *
 * @param[in]  img_width     width of output image
 * @param[in]  img_height    height of output image
 * @param[in]  out_buf       pointer to store output image, NULL may be used
 *                           if ei_camera_frame_buffer is to be used for capture and resize/cropping.
 *
 * @retval     false if not initialised, image captured, rescaled or cropped failed
 *
 */
bool ei_camera_capture(uint32_t img_width, uint32_t img_height, uint8_t *out_buf) {
  bool do_resize = false;

  if (!is_initialised) {
    ei_printf("ERR: Camera is not initialized\r\n");
    return false;
  }

  camera_fb_t *fb = esp_camera_fb_get();

  if (!fb) {
    ei_printf("Camera capture failed\n");
    return false;
  }

  bool converted = fmt2rgb888(fb->buf, fb->len, PIXFORMAT_JPEG, snapshot_buf);

  esp_camera_fb_return(fb);

  if (!converted) {
    ei_printf("Conversion failed\n");
    return false;
  }

  if ((img_width != EI_CAMERA_RAW_FRAME_BUFFER_COLS)
      || (img_height != EI_CAMERA_RAW_FRAME_BUFFER_ROWS)) {
    do_resize = true;
  }

  if (do_resize) {
    ei::image::processing::crop_and_interpolate_rgb888(
      out_buf,
      EI_CAMERA_RAW_FRAME_BUFFER_COLS,
      EI_CAMERA_RAW_FRAME_BUFFER_ROWS,
      out_buf,
      img_width,
      img_height);
  }


  return true;
}

static int ei_camera_get_data(size_t offset, size_t length, float *out_ptr) {
  // we already have a RGB888 buffer, so recalculate offset into pixel index
  size_t pixel_ix = offset * 3;
  size_t pixels_left = length;
  size_t out_ptr_ix = 0;

  while (pixels_left != 0) {
    // Swap BGR to RGB here
    // due to https://github.com/espressif/esp32-camera/issues/379
    out_ptr[out_ptr_ix] = (snapshot_buf[pixel_ix + 2] << 16) + (snapshot_buf[pixel_ix + 1] << 8) + snapshot_buf[pixel_ix];

    // go to the next pixel
    out_ptr_ix++;
    pixel_ix += 3;
    pixels_left--;
  }
  // and done!
  return 0;
}

/* WIFI FUNCTION */
void connect_wifi(void) {
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Connected!");
}

/* Correct CRC32-C (Castagnoli) encoding function */
uint32_t crc32_castagnoli_encode(const uint8_t *data, size_t length) {
  uint32_t crc = 0xFFFFFFFF;
  const uint32_t poly = 0x82F63B78;  // Reversed form of 0x1EDC6F41

  for (size_t i = 0; i < length; ++i) {
    crc ^= data[i];
    for (int j = 0; j < 8; ++j) {
      if (crc & 1)
        crc = ((crc >> 1) ^ poly) & 0xFFFFFFFF;
      else
        crc = (crc >> 1) & 0xFFFFFFFF;
    }
  }

  return (~crc) & 0xFFFFFFFF;
}

/* SEND FUNCTION */
void send_data(void) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected!");
    return;
  }

  String slotStr = "";
  for (int i = 0; i < 16; i++) {
    slotStr += (slot_values[i] ? "1" : "0");
    if (i < 15) slotStr += ",";
  }

  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String dataString = String(packed[0], HEX) + String(packed[1], HEX);
  // String payload = "slot=" + "\"" + slotStr + "\"" + "\nchecksum=0x" + String(checksum, HEX);
  String payload = String("slot=") + "\"" + slotStr + "\"" + "&checksum=0x" + String(checksum, HEX);
  Serial.println("Payload: " + payload);

  int httpResponseCode = http.POST(payload);
  if (httpResponseCode > 0) {
    Serial.printf("HTTP Response code: %d\n", httpResponseCode);
    String response = http.getString();
    Serial.println("Server Response: " + response);
  } else {
    Serial.print("Error on sending POST: ");
  }

  http.end();
}

/* BOOL-TO-BYTE FUNCTION */
void pack_bools_to_bytes(const bool *bools, uint8_t *output, size_t length) {
  output[0] = 0;
  output[1] = 0;

  for (size_t i = 0; i < length; ++i) {
    size_t byte_index = i / 8;
    size_t bit_index = i % 8;
    if (bools[i]) {
      output[byte_index] |= (1 << (7 - bit_index));
    }
  }
}

/* RANDOMIZER FUNCTION USING TRUE RANDOMNESS */
void randomize_slot_values(void) {
  for (int i = 0; i < 16; ++i) {
    slot_values[i] = esp_random() % 2;
  }
}

#if !defined(EI_CLASSIFIER_SENSOR) || EI_CLASSIFIER_SENSOR != EI_CLASSIFIER_SENSOR_CAMERA
#error "Invalid model for current sensor"
#endif