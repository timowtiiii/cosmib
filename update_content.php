<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['changes'])) {
        $changes = json_decode($_POST['changes'], true);

        foreach ($changes as $id => $content) {
            if ($id === 'page_content') {
                foreach ($content as $key => $value) {
                    $sql = "SELECT * FROM page_content WHERE section = 'page_content' AND content_key = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $key);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $sql = "UPDATE page_content SET content_value = ? WHERE section = 'page_content' AND content_key = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $value, $key);
                    } else {
                        $sql = "INSERT INTO page_content (section, content_key, content_value) VALUES ('page_content', ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $key, $value);
                    }
                    $stmt->execute();
                }
            } else {
                // This part is for updating products, which is already handled by save_product.php
                // You might want to merge the logic here or keep it separate
            }
        }
        echo "Content updated successfully.";
    } else {
        echo "Invalid data.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>