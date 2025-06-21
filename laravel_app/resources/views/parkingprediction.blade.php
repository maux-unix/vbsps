<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel='stylesheet' href='/css/parkingprediction.css'>
</head>

<body>
    <nav>
        <img src="/BackGroundForAll/Logo.png" alt="">
        <div class="outerNavLink">
            <a href="/">Live Parking Slot</a>
            <a href="/parking-prediction">Parking Prediction</a>
            <a href="/checksum-analysis">Checksum Analysis</a>
        </div>
        <img src="/BackGroundForAll/icons8-menu.svg" alt="Menu" class="menu-icon" onclick="toggleSideMenu()">

        <div class="side-menu" id="sideMenu">
            <a href="/">Live Parking Slot</a>
            <a href="/parking-prediction">Parking Prediction</a>
            <a href="/checksum-analysis">Checksum Analysis</a>
        </div>

    </nav>
    <div class="main-div">
        <div class="container">
            <div class="header">
                <h1>Parking Prediction</h1>
                <p class="description">The diagram shows the prediction for <strong>empty parking slot</strong> in <span
                        class="date">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span></p>

            </div>

            <div class="chart-container">
                <div class="axis-labels">
                    <span></span>
                    <span>4</span>
                    <span>8</span>
                    <span>12</span>
                    <span>16</span>
                </div>
                <div class="chart" id="chart">
                    <!-- Bars will be inserted here by JavaScript -->
                </div>
            </div>

            <div class="table-container">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Empty slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table rows will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script>

        function toggleSideMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('show');
        }

        // Parking data (time and empty slots)
        const timeSlots = [
            "06.00-07.00", "07.01-08.00", "08.01-09.00", "09.01-10.00", "10.01-09.00",
            "11.01-12.00", "12.01-13.00", "13.01-14.00", "14.01-15.00", "15.01-16.00",
            "16.01-17.00", "17.01-18.00", "18.01-19.00", "19.01-20.00", "20.01-21.00"
        ];

        // ambil data freeslots dari blade (PHP) ke JS
        const parkingSlotsFromServer = @json($freeslots);

        // buat array parkingData sesuai format yang lama
        const parkingData = timeSlots.map((time, idx) => ({
            time,
            slots: parkingSlotsFromServer[idx] ?? 0
        }));


        // Maximum value for scaling
        const maxSlots = 16;

        // Generate chart
        function generateChart() {
            const chartElement = document.getElementById('chart');
            chartElement.innerHTML = '';

            parkingData.forEach(data => {
                const barRow = document.createElement('div');
                barRow.className = 'bar-row';

                const timeLabel = document.createElement('div');
                timeLabel.className = 'time-label';
                timeLabel.textContent = data.time;

                const barContainer = document.createElement('div');
                barContainer.className = 'bar-container';

                const bar = document.createElement('div');
                bar.className = 'bar';
                bar.style.width = `${(data.slots / maxSlots) * 100}%`;
                if (data.slots > 0) {
                    bar.textContent = data.slots;
                }

                barContainer.appendChild(bar);
                barRow.appendChild(timeLabel);
                barRow.appendChild(barContainer);
                chartElement.appendChild(barRow);
            });
        }

        // Generate table
        function generateTable() {
            const tableBody = document.querySelector('#dataTable tbody');
            tableBody.innerHTML = '';

            parkingData.forEach(data => {
                const row = document.createElement('tr');

                const timeCell = document.createElement('td');
                timeCell.textContent = data.time;

                const slotsCell = document.createElement('td');
                slotsCell.textContent = data.slots;

                row.appendChild(timeCell);
                row.appendChild(slotsCell);
                tableBody.appendChild(row);
            });
        }


        // Update parking data
        function updateParkingData(newData) {
            // Update our data array
            for (let i = 0; i < parkingData.length; i++) {
                if (i < newData.length) {
                    parkingData[i].slots = newData[i];
                }
            }

            // Regenerate chart and table
            generateChart();
            generateTable();
        }

        // Initialize chart and table on page load
        document.addEventListener('DOMContentLoaded', function () {
            generateChart();
            generateTable();
        });

        // Example of how to update data (you could call this from an API)
        // Uncomment to test updating the visualization
        /*
        setTimeout(() => {
            // Example of new data
            const newValues = [18, 12, 6, 3, 1, 5, 14, 4, 5, 8, 13, 16, 18, 20, 18];
            updateParkingData(newValues);
        }, 3000);
        */
    </script>
</body>

</html>