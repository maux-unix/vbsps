<!DOCTYPE html>
<html>
<head>
    <title>Checksum Analysis</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <a href="/">Back to live parking</a>
    <h2>Data Checksum Analysis</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Slot Parkir</th>
                <th>Checksum AWS</th>
                <th>Checksum ESP32</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody id="data-body">
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->slot_parkir }}</td>
                    <td>{{ $item->checksum_aws }}</td>
                    <td>{{ $item->checksum_esp32 }}</td>
                    <td style="color: {{ $item->status ? 'green' : 'red' }}">
                        {{ $item->status ? '✅ Cocok' : '❌ Mismatch' }}
                    </td>
                    <td>{{ $item->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        setInterval(() => {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTbody = doc.querySelector('#data-body');
                    document.querySelector('#data-body').innerHTML = newTbody.innerHTML;
                });
        }, 5000); // refresh setiap 5 detik
    </script>

</body>
</html>
