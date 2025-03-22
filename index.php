<?php
date_default_timezone_set('Asia/Tokyo');

$servername = "machinedatanglobal.c4sty2dpq6yv.ap-northeast-1.rds.amazonaws.com";
$username = "Nglobal_root_NIW"; 
$password = "Niw_Machinedata_11089694"; 
$dbname = "NIW_GRID_SPV_machine_data_space_NG";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter values
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$grouping_level = isset($_POST['grouping_level']) ? $_POST['grouping_level'] : 'hourly';

$grid_p_sql = "SELECT date_entry, time_entry, grid_p 
               FROM factory1_site1 
               WHERE date_entry >= '$start_date' AND date_entry <= '$end_date'
               ORDER BY date_entry DESC, time_entry DESC";
$grid_p_result = $conn->query($grid_p_sql);

$factory_consumption_sql = "SELECT datee, timee, power_now
                            FROM factory1_consumption
                            WHERE datee >= '$start_date' AND datee <= '$end_date'
                            ORDER BY datee ASC, timee DESC";
$factory_consumption_result = $conn->query($factory_consumption_sql);



$grid_p_data = [];
while ($row = $grid_p_result->fetch_assoc()) {
    $grid_p_data[] = $row;
}

$factory_consumption_data = [];
while ($row = $factory_consumption_result->fetch_assoc()) {
    $factory_consumption_data[] = $row;
}

$conn->close();

// Group Data
function groupData($data, $date_key, $value_key, $grouping) {
    $grouped_data = [];
    foreach ($data as $entry) {
        $date = $entry[$date_key];
        if ($grouping === "monthly") {
            $key = date("Y-m", strtotime($date));
        } else {
            $key = $date;
        }

        if (!isset($grouped_data[$key])) {
            $grouped_data[$key] = 0;
        }
        $grouped_data[$key] += floatval($entry[$value_key]);
    }
    return $grouped_data;
}

$grouped_grid_p = groupData($grid_p_data, "date_entry", "grid_p", $grouping_level);
$grouped_factory_p = groupData($factory_consumption_data, "datee", "power_now", $grouping_level);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Power Consumption Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Factory Power Consumption Chart</h2>

    <form method="POST">
        Start Date: <input type="date" name="start_date" required value="<?= $start_date ?>">
        End Date: <input type="date" name="end_date" required value="<?= $end_date ?>">
        <select name="grouping_level">
            <option value="hourly" <?= $grouping_level == "hourly" ? "selected" : "" ?>>Hourly</option>
            <option value="daily" <?= $grouping_level == "daily" ? "selected" : "" ?>>Daily</option>
            <option value="monthly" <?= $grouping_level == "monthly" ? "selected" : "" ?>>Monthly</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    <canvas id="powerChart"></canvas>

    <script>
        let labels = <?= json_encode(array_keys($grouped_grid_p)) ?>;
        let gridPower = <?= json_encode(array_values($grouped_grid_p)) ?>;
        let factoryPower = <?= json_encode(array_values($grouped_factory_p)) ?>;

        let ctx = document.getElementById("powerChart").getContext("2d");
        let powerChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Grid Power (kW)",
                        backgroundColor: "rgba(75, 192, 192, 0.7)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 1,
                        data: gridPower
                    },
                    {
                        label: "Factory Consumption (kW)",
                        backgroundColor: "rgba(255, 99, 132, 0.7)",
                        borderColor: "rgba(255, 99, 132, 1)",
                        borderWidth: 1,
                        data: factoryPower
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: "Date" } },
                    y: { title: { display: true, text: "Power (kW)" } }
                }
            }
        });
    </script>
</body>
</html>



