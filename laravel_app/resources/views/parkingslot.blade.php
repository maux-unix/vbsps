<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Parking Slot</title>
    <link rel="stylesheet" href="{{ asset('css/parkingslot.css') }}">
</head>

<body>
    <nav>
        <img src="{{ asset('BackGroundForAll/Logo.png') }}" alt="">
        <div class="outerNavLink">
            <a href="/">Live Parking Slot</a>
            <a href="/parking-prediction">Parking Prediction</a>
            <a href="/checksum-analysis">Checksum Analysis</a>
        </div>
        <img src="{{ asset('BackGroundForAll/icons8-menu.svg') }}" alt="Menu" class="menu-icon" onclick="toggleSideMenu()">
        <div class="side-menu" id="sideMenu">
            <a href="/">Live Parking Slot</a>
            <a href="/parking-prediction">Parking Prediction</a>
            <a href="/checksum-analysis">Checksum Analysis</a>
        </div>
    </nav>

    <div class="main-div">
        <div class="header">
            <h1>Live Parking Slot</h1>
            <p class="update-time">Last updated: {{ $date }} {{ $time }}</p>
	 
        </div>
	<p>Free Slots = {{$freeslot}} slots</p>
        <p class="keterangan">
            <span class="red">Red</span> means someone park there,
            <span class="blank">Blank</span> means empty parking slot
        </p>
        <div class="outerContainer">
                <div class="container">
                <div class="exit">entrance</div>    
                <div class="entrance">exit</div>
                
                <div class="seat-container" id="seatContainer"></div>
                <div class="controls">
                    <button onclick="randomizeSeats()">Randomize Seats</button>
                    <button onclick="resetSeats()">Reset All</button>
                </div>
            </div>
        </div>
    </div>

    <script>
         // Inject server data into JS
// let seatStatus = @json(array_map(fn($v) => $v === 1, $slot));

// function renderSeats() {
//     const seatContainer = document.getElementById('seatContainer');
//     seatContainer.innerHTML = '';
//     const isMobile = window.innerWidth < 1000;

//     // Layout untuk desktop: 2 kolom x 8 baris
//     // Layout untuk mobile: 8 kolom x 2 baris
    
//     if (isMobile) {
//         // Mobile layout: 8x2 grid
//         // Baris 1: index 0-7, Baris 2: index 8-15
//         for (let row = 0; row < 2; row++) {
//             for (let col = 0; col < 8; col++) {
//                 const dataIndex = row * 8 + col;
//                 createSeat(dataIndex, dataIndex, isMobile);
//             }
//         }
//     } else {
//         // Desktop layout: 2x8 grid
//         // Kolom 1: index 7,6,5,4,3,2,1,0 (dari atas ke bawah)
//         // Kolom 2: index 15,14,13,12,11,10,9,8 (dari atas ke bawah)
//         const customOrder = [
//             7, 6, 5, 4, 3, 2, 1, 0,    // Kolom kiri
//             15, 14, 13, 12, 11, 10, 9, 8  // Kolom kanan
//         ];
        
//         for (let i = 0; i < customOrder.length; i++) {
//             const dataIndex = customOrder[i];
//             createSeat(i, dataIndex, isMobile);
//         }
//     }
// }

// function createSeat(visualIndex, dataIndex, isMobile) {
//     const seatContainer = document.getElementById('seatContainer');
//     const seat = document.createElement('div');
    
//     // Set class dan status kursi
//     seat.className = `seat ${seatStatus[dataIndex] ? 'red' : 'white'}`;
//     seat.dataset.index = dataIndex;

//     // Event listener untuk klik
//     seat.addEventListener('click', () => {
//         seatStatus[dataIndex] = !seatStatus[dataIndex];
//         renderSeats();
//     });

//     // Kondisi untuk kursi abu-abu (tidak bisa digunakan)
//     // Index 0 dan 8 adalah kursi abu-abu (kolom pertama di desktop)
//     const grayIndexes = [7, 15];
    
//     if (grayIndexes.includes(dataIndex)) {
//         seat.style.backgroundColor = 'gray';
//         seat.style.pointerEvents = 'none';
//         seat.classList.remove('red', 'white');
//         seat.classList.add('gray');
//     }

//     seatContainer.appendChild(seat);
// }

// function randomizeSeats() {
//     // Reset semua kursi kecuali yang abu-abu
//     for (let i = 0; i < 16; i++) {
//         if (![0, 8].includes(i)) { // Jangan randomize kursi abu-abu
//             seatStatus[i] = Math.random() > 0.7;
//         }
//     }
//     renderSeats();
// }

// function resetSeats() {
//     // Reset semua kursi ke false kecuali yang abu-abu
//     for (let i = 0; i < 16; i++) {
//         if (![0, 8].includes(i)) {
//             seatStatus[i] = false;
//         }
//     }
//     renderSeats();
// }

// function toggleSideMenu() {
//     document.getElementById('sideMenu').classList.toggle('show');
// }

// window.onload = () => {
//     renderSeats();
    
//     // Event listener untuk resize
//     let resizeTimeout;
//     window.addEventListener('resize', () => {
//         clearTimeout(resizeTimeout);
//         resizeTimeout = setTimeout(renderSeats, 100); // Debounce resize
//     });
    
//     // Auto-refresh every 4 seconds
//     setInterval(() => { 
//         window.location.reload(); 
//     }, 4000);
// };

    // Inject server data into JS
let seatStatus = @json(array_map(fn($v) => $v === 1, $slot));

function renderSeats() {
    const seatContainer = document.getElementById('seatContainer');
    seatContainer.innerHTML = '';
    const isMobile = window.innerWidth < 1000;

    // Layout untuk desktop: 2 kolom x 8 baris
    // Layout untuk mobile: 8 kolom x 2 baris
    
    if (isMobile) {
        // Mobile layout: 2x8 grid vertikal
        // Kolom kiri: 15,14,13,12,11,10,9,8 (kolom kanan desktop)
        // Kolom kanan: 7,6,5,4,3,2,1,0 (kolom kiri desktop)
        // const mobileOrder = [
        //     15, 7,   // Baris 1
        //     14, 6,   // Baris 2
        //     13, 5,   // Baris 3
        //     12, 4,   // Baris 4
        //     11, 3,   // Baris 5
        //     10, 2,   // Baris 6
        //     9, 1,    // Baris 7
        //     8, 0     // Baris 8
        // ];
        
        // const mobileOrder = [
        //     8, 0,   // Baris 1
        //     9, 1,   // Baris 2
        //     10, 2,   // Baris 3
        //     11, 3,   // Baris 4
        //     12, 4,   // Baris 5
        //     13, 5,   // Baris 6
        //     14, 6,    // Baris 7
        //     15, 7     // Baris 8
        // ];

        const mobileOrder = [
            0, 8,   // Baris 1
            1, 9,   // Baris 2
            2, 10,   // Baris 3
            3, 11,   // Baris 4
            4, 12,   // Baris 5
            5, 13,   // Baris 6
            6, 14,    // Baris 7
            7, 15     // Baris 8
        ];
        for (let i = 0; i < mobileOrder.length; i++) {
            const dataIndex = mobileOrder[i];
            createSeat(i, dataIndex, isMobile);
        }
    } else {
        // Desktop layout: 2x8 grid
        // Kolom 1: index 7,6,5,4,3,2,1,0 (dari atas ke bawah)
        // Kolom 2: index 15,14,13,12,11,10,9,8 (dari atas ke bawah)
        //const customOrder = [
        //    7, 6, 5, 4, 3, 2, 1, 0,    // Kolom kiri
        //    15, 14, 13, 12, 11, 10, 9, 8  // Kolom kanan
        //];

        const customOrder = [
            8, 9, 10, 11, 12, 13, 14, 15,
            0, 1, 2, 3, 4, 5, 6, 7    // Kolom kiri
             // Kolom kanan
        ];
        
        for (let i = 0; i < customOrder.length; i++) {
            const dataIndex = customOrder[i];
            createSeat(i, dataIndex, isMobile);
        }
    }
}

function createSeat(visualIndex, dataIndex, isMobile) {
    const seatContainer = document.getElementById('seatContainer');
    const seat = document.createElement('div');
    
    // Set class dan status kursi
    seat.className = `seat ${seatStatus[dataIndex] ? 'red' : 'white'}`;
    seat.dataset.index = dataIndex;

    // Event listener untuk klik
    seat.addEventListener('click', () => {
        seatStatus[dataIndex] = !seatStatus[dataIndex];
        renderSeats();
    });

    // Kondisi untuk kursi abu-abu (tidak bisa digunakan)
    // Index 0 dan 8 adalah kursi abu-abu (kolom pertama di desktop)
    const grayIndexes = [7, 0, 15, 8];
    
    if (grayIndexes.includes(dataIndex)) {
        seat.style.backgroundColor = 'gray';
        seat.style.pointerEvents = 'none';
        seat.classList.remove('red', 'white');
        seat.classList.add('gray');
    }

    seatContainer.appendChild(seat);
}

function randomizeSeats() {
    // Reset semua kursi kecuali yang abu-abu
    for (let i = 0; i < 16; i++) {
        if (![0, 8].includes(i)) { // Jangan randomize kursi abu-abu
            seatStatus[i] = Math.random() > 0.7;
        }
    }
    renderSeats();
}

function resetSeats() {
    // Reset semua kursi ke false kecuali yang abu-abu
    for (let i = 0; i < 16; i++) {
        if (![0, 8].includes(i)) {
            seatStatus[i] = false;
        }
    }
    renderSeats();
}

function toggleSideMenu() {
    document.getElementById('sideMenu').classList.toggle('show');
}

window.onload = () => {
    renderSeats();
    
    // Event listener untuk resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(renderSeats, 100); // Debounce resize
    });
    
    // Auto-refresh every 4 seconds
    setInterval(() => { 
        window.location.reload(); 
    }, 4000);
};
    </script>
</body>

</html>

