<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$xray_id = isset($_GET['xray_id']) ? intval($_GET['xray_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

$patient_id = $_SESSION['patient_id'];

// Debug: Check session and URL parameters
echo "<script>console.log('Session Patient ID: $patient_id');</script>";
echo "<script>console.log('Requested X-ray ID: $xray_id');</script>";

// Verify that this X-ray belongs to the logged-in patient
$sql = "SELECT xi.*, CONCAT(up.first_name, ' ', up.last_name) as patient_name 
        FROM xray_images xi 
        JOIN patient_profiles up ON xi.patient_id = up.patient_id 
        WHERE xi.xray_id = ? AND xi.patient_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $xray_id, $patient_id);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Set default values and indicate no record was found
    $xray_name = "N/A";
    $xray_date = "N/A";
    $description = "No description available.";
    $no_record_found = true;
    echo "<script>console.log('No matching X-ray found for X-ray ID $xray_id and Patient ID $patient_id');</script>";
} else {
    // Fetch data and set variables
    $row = $result->fetch_assoc();
    $no_record_found = false;

    $xray_name = $row['xray_name'];
    $xray_date = date('F d, Y', strtotime($row['xray_date']));
    $description = $row['description'];
    $db_image_path = ltrim($row['image_path'], '/');
    $web_path = '/' . $db_image_path;
    $full_server_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $db_image_path;

    // Debug paths
    echo "<script>console.log('Database Image Path: $db_image_path');</script>";
    echo "<script>console.log('Web Path: $web_path');</script>";
    echo "<script>console.log('Full Server Path: $full_server_path');</script>";
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/view_xray.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <title>View X-ray - <?php echo htmlspecialchars($xray_name); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="medical-dashboard">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-laptop-medical"></i>
                </div>
            </div>
        </header>

        <div class="xray-container">
            <aside class="patient-sidebar">
                <div class="patient-info">
                    <h2><i class="fas fa-user-circle"></i> PATIENT DETAILS</h2>
                    <?php if (!$no_record_found): ?>
                        <p><strong>NAME:</strong> <?php echo htmlspecialchars($row['patient_name']); ?></p>
                    <?php endif; ?>
                </div>

            </aside>

            <main class="main-content">
                <div class="xray-header">
                    <div class="xray-title">
                        <h1><?php echo htmlspecialchars($xray_name); ?></h1>
                        <div class="xray-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($xray_date); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($no_record_found): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        X-ray not found or access denied.
                    </div>
                <?php elseif (file_exists($full_server_path)): ?>
                    <div class="image-viewer" id="imageViewer">
                        <div class="image-controls">
                            <button class="control-button" id="zoomIn" title="Zoom In">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="control-button" id="zoomOut" title="Zoom Out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button class="control-button" id="resetZoom" title="Reset">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                        <img src="<?php echo htmlspecialchars($web_path); ?>" alt="X-ray Image" class="xray-image"
                            id="xrayImage">
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error: Image file not found.
                    </div>
                    <div class="debug-info">
                        <strong><i class="fas fa-bug"></i> Debug Information:</strong>
                        Database Path: <?php echo htmlspecialchars($db_image_path); ?><br>
                        Full Server Path: <?php echo htmlspecialchars($full_server_path); ?><br>
                        Web Path: <?php echo htmlspecialchars($web_path); ?><br>
                        Script Directory: <?php echo htmlspecialchars(__DIR__); ?><br>
                        Parent Directory: <?php echo htmlspecialchars(dirname(__DIR__)); ?><br>
                        File Exists: <?php echo file_exists($full_server_path) ? 'Yes' : 'No'; ?><br>
                        Current Permissions:
                        <?php echo file_exists($full_server_path) ? substr(sprintf('%o', fileperms($full_server_path)), -4) : 'N/A'; ?>
                    </div>
                <?php endif; ?>

                <div class="xray-details">
                    <h3><i class="fas fa-file-medical-alt"></i> Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                </div>
                <a href="../medical_documentation.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> BACK 
                </a>
            </main>
        </div>
    </div>
</body>
<script src="/js/image_controls.js"></script>
<script src="/js/sidebar.js"></script>
</script>

</html>