<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

$check_deleted_column = "SHOW COLUMNS FROM menu_items LIKE 'is_deleted'";
$column_exists = $conn->query($check_deleted_column);
if ($column_exists->num_rows == 0) {
    $add_column = "ALTER TABLE menu_items ADD COLUMN is_deleted TINYINT(1) DEFAULT 0";
    $conn->query($add_column);
}

if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    
    $delete_sql = "UPDATE menu_items SET is_deleted = 1, is_visible = 0 WHERE id = ? AND seller_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $item_id, $_SESSION['seller_id']);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Item has been successfully deleted.";
    } else {
        $_SESSION['error'] = "Failed to delete the item.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $item_quantity = isset($_POST['item_quantity']) ? (int)$_POST['item_quantity'] : 0;
    
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../uploads/menu_items/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = "uploads/menu_items/" . $new_filename;
        }
    }
    
    if (isset($_POST['item_id']) && !empty($_POST['item_id'])) {
        $item_id = $_POST['item_id'];
        $sql = "UPDATE menu_items SET 
                name = ?, 
                description = ?, 
                price = ?, 
                category = ?, 
                is_visible = ?,
                item_quantity = ?";
        
        if ($image_path) {
            $sql .= ", image = ?";
        }
        
        $sql .= " WHERE id = ? AND seller_id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($image_path) {
            $stmt->bind_param("ssdsisii", $name, $description, $price, $category, $is_visible, $item_quantity, $image_path, $item_id, $_SESSION['seller_id']);
        } else {
            $stmt->bind_param("ssdsiiii", $name, $description, $price, $category, $is_visible, $item_quantity, $item_id, $_SESSION['seller_id']);
        }
    } else {
        $sql = "INSERT INTO menu_items (seller_id, name, description, price, category, is_visible, image, available, featured, item_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $available_val = 1;
        $featured_val = 0;
        $stmt->bind_param("issdsisiii", $_SESSION['seller_id'], $name, $description, $price, $category, $is_visible, $image_path, $available_val, $featured_val, $item_quantity);
    }
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Item saved successfully!";
        } else {
            if (!isset($_POST['item_id'])) { 
                $_SESSION['error'] = "Item insertion reported success, but no rows were added. DB error: " . $conn->error;
            } else {
                $_SESSION['success'] = "Item saved successfully (no changes detected or applied).";
            }
        }
    } else {
        $_SESSION['error'] = "Error saving item: " . $conn->error;
    }
    
    header("Location: manage_menu.php");
    exit();
}

if (isset($_POST['toggle_status']) && isset($_POST['item_id']) && isset($_POST['status'])) {
    $item_id = $_POST['item_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE menu_items SET status = ? WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $item_id, $_SESSION['seller_id']);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        exit();
    }
}

if (isset($_POST['toggle_visibility'])) {
    $item_id = $_POST['item_id'];
    $new_visibility = $_POST['visibility'];
    
    $sql = "UPDATE menu_items SET is_visible = ? WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $new_visibility, $item_id, $_SESSION['seller_id']);
    $stmt->execute();
    exit();
}

$check_column = "SHOW COLUMNS FROM menu_items LIKE 'is_visible'";
$column_exists = $conn->query($check_column);
if ($column_exists->num_rows == 0) {
    $add_column = "ALTER TABLE menu_items ADD COLUMN is_visible TINYINT(1) DEFAULT 1";
    $conn->query($add_column);
}

// Check and add item_quantity column if it doesn't exist
$check_quantity_column = "SHOW COLUMNS FROM menu_items LIKE 'item_quantity'";
$quantity_column_exists = $conn->query($check_quantity_column);
if ($quantity_column_exists->num_rows == 0) {
    $add_quantity_column = "ALTER TABLE menu_items ADD COLUMN item_quantity INT DEFAULT 0";
    $conn->query($add_quantity_column);
}

$sql = "SELECT *, is_visible FROM menu_items WHERE seller_id = ? AND is_deleted = 0 ORDER BY category, name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['seller_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .header h2 {
            margin: 0;
            color: #186479;
            font-size: 24px;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            font-size: 16px;
        }

        .btn-primary {
            background: #186479;
            color: white;
        }

        .btn-primary:hover {
            background: #134d5d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 100, 121, 0.2);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: #186479;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: #134d5d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 100, 121, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background-color: #186479;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 15px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            font-size: 15px;
            background-color: #f8f9fa;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #186479;
            outline: none;
            box-shadow: 0 0 0 3px rgba(24, 100, 121, 0.1);
            background-color: #fff;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #186479;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .file-input-group {
            position: relative;
            margin-top: 10px;
        }

        .file-input-group input[type="file"] {
            padding: 12px;
            background: #f8f9fa;
            border: 2px dashed #186479;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
        }

        .file-input-group input[type="file"]:hover {
            background: #f1f3f5;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .modal-buttons .btn {
            min-width: 120px;
            padding: 12px 25px;
            font-size: 15px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 30px auto;
            padding: 35px;
            width: 90%;
            max-width: 600px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
            z-index: 1001;
        }

        .close-modal:hover {
            color: #186479;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 8px;
            display: none;
        }

        .image-preview.show {
            display: block;
        }

        .visibility-toggle {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .visibility-toggle.visible {
            background: #28a745;
            color: white;
        }

        .visibility-toggle.hidden {
            background: #dc3545;
            color: white;
        }

        .visibility-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
       <div class="app-header">
           <div class="logo">
              <img src="assets/logo.png" style="height: 40px;">          </div>
          <div class="header-title">    
          </div>
       </div>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        <div class="header">
            <h2>Menu Management</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="openAddItemModal()">
                Add New Item
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if (isset($item['image']) && !empty($item['image'])): ?>
                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" class="item-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                        <div class="item-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            No Image
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td><?php echo htmlspecialchars($item['item_quantity'] ?? 0); ?></td>
                    <td>
                        <?php $isVisible = isset($item['is_visible']) ? $item['is_visible'] : 1; ?>
                        <button class="visibility-toggle <?php echo ($isVisible == 1) ? 'visible' : 'hidden'; ?>"
                                onclick="toggleVisibility(this, <?php echo $item['id']; ?>)">
                            <?php echo ($isVisible == 1) ? 'Visible' : 'Hidden'; ?>
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-primary edit-item" 
                                data-item='<?php echo json_encode($item); ?>'
                                onclick="editItem(this)">
                            Edit
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="delete_item" class="btn btn-danger">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div id="addItemModal" class="modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeModal()">&times;</button>
                <h3>Add New Item</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" id="item_id">
                    
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="item_name" placeholder="Enter item name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="item_description" placeholder="Enter item description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" name="price" id="item_price" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="item_category" required>
                            <option value="">Select a category</option>
                            <option value="meal">Meal</option>
                            <option value="drinks">Drinks</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="item_quantity" id="item_quantity" min="0" value="0" required>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_visible" id="item_visibility" checked>
                        <label for="item_visibility">Available to Clients</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Image</label>
                        <div class="file-input-group">
                            <input type="file" name="image" id="item_image" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <img id="imagePreview" class="image-preview" src="" alt="Preview">
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                        <button type="submit" name="submit_item" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddItemModal() {
            document.getElementById('item_id').value = '';
            document.getElementById('item_name').value = '';
            document.getElementById('item_description').value = '';
            document.getElementById('item_price').value = '';
            document.getElementById('item_category').value = 'meal';
            document.getElementById('item_visibility').checked = true;
            document.getElementById('item_quantity').value = '0';
            document.getElementById('imagePreview').src = '';
            document.getElementById('imagePreview').classList.remove('show');
            
            document.querySelector('#addItemModal h3').textContent = 'Add New Item';
            document.getElementById('addItemModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function editItem(button) {
            const item = JSON.parse(button.dataset.item);
            document.getElementById('item_id').value = item.id;
            document.getElementById('item_name').value = item.name;
            document.getElementById('item_description').value = item.description;
            document.getElementById('item_price').value = item.price;
            document.getElementById('item_category').value = item.category;
            document.getElementById('item_visibility').checked = item.is_visible == 1;
            document.getElementById('item_quantity').value = item.item_quantity ?? 0;
            
            const preview = document.getElementById('imagePreview');
            if (item.image) {
                preview.src = '../' + item.image;
                preview.classList.add('show');
            } else {
                preview.src = '';
                preview.classList.remove('show');
            }
            
            document.getElementById('addItemModal').style.display = 'block';
            document.querySelector('#addItemModal h3').textContent = 'Edit Item';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('addItemModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function toggleVisibility(button, itemId) {
            const isCurrentlyVisible = button.classList.contains('visible');
            const newVisibility = isCurrentlyVisible ? 0 : 1;
            
            const formData = new FormData();
            formData.append('toggle_visibility', '1');
            formData.append('item_id', itemId);
            formData.append('visibility', newVisibility);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    button.classList.toggle('visible');
                    button.classList.toggle('hidden');
                    button.textContent = newVisibility ? 'Visible' : 'Hidden';
                } else {
                    alert('Failed to update visibility');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update visibility');
            });
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('show');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                preview.classList.remove('show');
            }
        }
    </script>
</body>
</html> 