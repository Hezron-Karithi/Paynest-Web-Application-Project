<?php
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/auth_guard.php';
$business_id = $_SESSION['business_id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT v.id, v.plate_number, r.id as route_id, 
           r.origin, r.destination, r.fare
    FROM vehicles v
    LEFT JOIN routes r ON v.route_id = r.id
    WHERE v.business_id = ? AND v.status = 'ACTIVE'
");
$stmt->execute([$business_id]);
$vehicles = $stmt->fetchAll();
$stmtTrips = $pdo->prepare("
    SELECT t.*, v.plate_number
    FROM trip_revenue t
    LEFT JOIN vehicles v ON t.vehicle_id = v.id
    WHERE t.business_id = ?
    ORDER BY t.trip_date DESC
");
$stmtTrips->execute([$business_id]);
$trips = $stmtTrips->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trip Revenue</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00;}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{margin:0;background:linear-gradient(135deg,var(--orange),var(--orange-dark));min-height:100vh;padding:20px;}
.container{max-width:1000px;margin:auto;}
.card{background:#fff;border-radius:16px;padding:24px;margin-bottom:25px;box-shadow:0 10px 25px rgba(0,0,0,0.1);}
h2,h3{text-align:center;margin-top:0;}
input,select{width:100%;padding:12px;margin-bottom:15px;border:1px solid #ddd;border-radius:10px;}
.btn-wrap{display:flex;justify-content:center;margin-top:10px;}
.btn{background:var(--orange);color:#fff;border:none;padding:14px 30px;border-radius:30px;font-weight:600;cursor:pointer;transition:.3s;}
.btn:hover{background:var(--orange-dark);transform:scale(1.05);}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid #eee;font-size:14px;text-align:left;}
.alert{padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;font-weight:600;}
.success{background:#e6fff5;color:#06d6a0;}
.error{background:#ffe6e6;color:#ef476f;}
@media(max-width:600px){.btn{width:100%;}}
</style>
<script>
const vehicleData = <?= json_encode($vehicles) ?>;
function updateFare() {
    const vehicleId = document.getElementById('vehicle_id').value;
    const vehicle = vehicleData.find(v => v.id == vehicleId);
    if (vehicle && vehicle.fare) {
        document.getElementById('fare').value = vehicle.fare;
    } else {
        document.getElementById('fare').value = '';
    }
    calculateTotal();
}
function calculateTotal() {
    const fare = parseFloat(document.getElementById('fare').value) || 0;
    const passengers = parseInt(document.getElementById('passengers').value) || 0;
    document.getElementById('total').value = (fare * passengers).toFixed(2);
}
</script>
</head>
<body>
<div class="container">
<div class="card">
<h2>Record Trip Revenue</h2>
<?php if(isset($_GET['success'])): ?>
<div class="alert success">Trip recorded successfully.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<div class="alert error">Something went wrong. Try again.</div>
<?php endif; ?>
<form method="POST" action="process_trip.php">
<label>Select Vehicle</label>
<select name="vehicle_id" id="vehicle_id" required onchange="updateFare()">
<option value="">-- Select Vehicle --</option>
<?php foreach($vehicles as $v): ?>
<option value="<?= $v['id'] ?>">
<?= htmlspecialchars($v['plate_number']) ?> 
(<?= htmlspecialchars($v['origin']) ?> → <?= htmlspecialchars($v['destination']) ?>)
</option>
<?php endforeach; ?>
</select>
<label>Fare Per Passenger</label>
<input type="number" step="0.01" name="fare" id="fare" readonly>
<label>Number of Passengers</label>
<input type="number" name="passengers" id="passengers" required oninput="calculateTotal()">
<label>Total Revenue</label>
<input type="number" step="0.01" name="total_amount" id="total" readonly>
<label>Trip Date</label>
<input type="date" name="trip_date" required>
<div class="btn-wrap">
<button type="submit" class="btn">Save Trip</button>
</div>
</form>
</div>
<div class="card">
<h3>Trip Records</h3>
<table>
<tr>
<th>Date</th>
<th>Vehicle</th>
<th>Passengers</th>
<th>Total Revenue</th>
</tr>
<?php foreach($trips as $t): ?>
<tr>
<td><?= htmlspecialchars($t['trip_date']) ?></td>
<td><?= htmlspecialchars($t['plate_number']) ?></td>
<td><?= number_format($t['passengers']) ?></td>
<td>KES <?= number_format($t['total_amount'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>
</body>
</html>
