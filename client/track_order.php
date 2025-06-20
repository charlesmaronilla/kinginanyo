<?php
session_start();
include '../includes/db_connect.php';

$has_preparing_items = false;
$order_status = null;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$user_query = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header('Location: login.php');
    exit();
}

$client_name = mysqli_real_escape_string($conn, $user['name']);
$result = mysqli_query($conn, "SELECT * FROM orders WHERE client_name = '$client_name' ORDER BY order_time DESC LIMIT 1");

if ($row = mysqli_fetch_assoc($result)) {
    $order_status = $row;
} else {
    $order_status = 'not_found';
}

if (is_array($order_status)) {
    $items_query = mysqli_query($conn, "
        SELECT 
            oi.*,
            mi.name as item_name,
            mi.price,
            s.stall_name
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN seller s ON oi.seller_id = s.id
        WHERE oi.order_id = " . $order_status['id'] . "
        ORDER BY s.stall_name, mi.name
    ");

    while ($item = mysqli_fetch_assoc($items_query)) {
        if ($item['status'] === 'Preparing') {
            $has_preparing_items = true;
            break;
        }
    }
    
    if ($items_query) {
        mysqli_data_seek($items_query, 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Order</title>
  <link rel="stylesheet" href="assets/css/trackorder.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sockjs-client/1.5.0/sockjs.min.js"></script>
  <style>
    .countdown-container {
      background-color: #f8f9fa;
      border: 2px solid #28a745;
      border-radius: 8px;
      padding: 15px;
      margin: 20px 0;
      text-align: center;
    }
    
    .countdown-container p {
      margin: 0;
      font-size: 1.1em;
      color: #333;
    }
    
    #countdown {
      font-weight: bold;
      color: #28a745;
      font-size: 1.2em;
    }

    @keyframes slideDown {
      from {
        transform: translate(-50%, -100%);
        opacity: 0;
      }
      to {
        transform: translate(-50%, 0);
        opacity: 1;
      }
    }
    
    @keyframes slideUp {
      from {
        transform: translate(-50%, 0);
        opacity: 1;
      }
      to {
        transform: translate(-50%, -100%);
        opacity: 0;
      }
    }

    .order-ready-notification {
      position: fixed;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      background: #4caf50;
      color: white;
      padding: 16px 32px;
      border-radius: 0 0 8px 8px;
      font-size: 18px;
      z-index: 9999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: flex;
      flex-direction: column;
      gap: 8px;
      animation: slideDown 0.3s ease-out;
    }

    .notification-content {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification-progress {
      height: 3px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 2px;
      width: 100%;
    }
  </style>
  <script>
    let socket;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 1000;

    function connectWebSocket() {
        socket = new WebSocket('ws://localhost:8080');
        
        socket.onopen = function(e) {
            console.log('Connected to notification server');
            reconnectAttempts = 0;
        };
        
        socket.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.type === 'order_status' && data.orderId === '<?php echo $order_status['id']; ?>') {
                if (data.status === 'Ready') {
                    showNotification('Your order is ready to be claimed!');
                }
            }
        };
        
        socket.onerror = function(error) {
            console.error('WebSocket error:', error);
        };
        
        socket.onclose = function(event) {
            console.log('WebSocket connection closed');
            if (reconnectAttempts < maxReconnectAttempts) {
                reconnectAttempts++;
                setTimeout(connectWebSocket, reconnectDelay * reconnectAttempts);
            }
        };
    }

    connectWebSocket();

    setInterval(() => {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'heartbeat' }));
        }
    }, 30000);

    function showNotification(message) {
        if (document.getElementById('order-ready-notif')) return;
        
        const notification = document.createElement('div');
        notification.id = 'order-ready-notif';
        notification.className = 'order-ready-notification';
        
        const progressBar = document.createElement('div');
        progressBar.className = 'notification-progress';
        
        const messageContainer = document.createElement('div');
        messageContainer.className = 'notification-content';
        messageContainer.innerHTML = '<i class="fas fa-bell"></i> ' + message;
        
        notification.appendChild(messageContainer);
        notification.appendChild(progressBar);
        
        document.body.appendChild(notification);
        
        progressBar.style.width = '100%';
        progressBar.style.transition = 'width 3s linear';
        
        setTimeout(() => {
            notification.style.animation = 'slideUp 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
  </script>
</head>
<body>
  <div class="track-container">
    <h1>Track Your Order</h1>
    <?php if ($order_status === 'not_found'): ?>
      <p>No recent order found for your account.</p>
    <?php elseif (is_array($order_status)): ?>
      <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
      <div class="order-status">
        <h3>Order Details</h3>
        <p><strong>Reference No:</strong> <?php echo $order_status['id']; ?></p>
        <p><strong>Status:</strong> <?php echo $order_status['status']; ?></p>
        <p><strong>Ordered On:</strong> <?php echo date('F j, Y g:i A', strtotime($order_status['order_time'])); ?></p>
        <?php if (!empty($order_status['special_request'])): ?>
        <p><strong>Special Request:</strong> <?php echo htmlspecialchars($order_status['special_request']); ?></p>
        <?php endif; ?>
        
        <?php if ($order_status['is_reservation']): ?>
        <p><strong>Order Type:</strong> Advance Order</p>
        <p><strong>Pick-up Date:</strong> <?php echo date('F j, Y', strtotime($order_status['reservation_date'])); ?></p>
        <p><strong>Pick-up Time:</strong> <?php echo date('g:i A', strtotime($order_status['reservation_time'])); ?></p>
        <?php endif; ?>

        <?php if ($has_preparing_items): ?>
        <div id="countdown-container" class="countdown-container">
          <p>Your order is being prepared! Take a Photo of your Order! Redirecting to dashboard in <span id="countdown">7</span> seconds...</p>
        </div>
        <?php endif; ?>

        <h4>Order Items</h4>
        <?php
        $items_query = mysqli_query($conn, "
            SELECT 
                oi.*,
                mi.name as item_name,
                mi.price,
                s.stall_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN seller s ON oi.seller_id = s.id
            WHERE oi.order_id = " . $order_status['id'] . "
            ORDER BY s.stall_name, mi.name
        ");

        $current_stall = '';
        while ($item = mysqli_fetch_assoc($items_query)) {
            if ($current_stall !== $item['stall_name']) {
                if ($current_stall !== '') {
                    echo '</div>';
                }
                $current_stall = $item['stall_name'];
                echo '<div class="stall-section">';
                echo '<h5>' . htmlspecialchars($item['stall_name']) . '</h5>';
            }
            ?>
            <div class="order-item">
                <p>
                    <?php echo htmlspecialchars($item['item_name']); ?> × <?php echo $item['quantity']; ?>
                    <span class="item-status">(<?php echo $item['status']; ?>)</span>
                </p>
            </div>
            <?php
        }
        if ($current_stall !== '') {
            echo '</div>';
        }
        ?>
      </div>
    <?php endif; ?>

    <a href="dashboard.php">← Back to Dashboard</a>
  </div>

  <?php if ($has_preparing_items): ?>
  <script>
    let timeLeft = 7;
    const countdownElement = document.getElementById('countdown');
    
    const countdown = setInterval(() => {
      timeLeft--;
      countdownElement.textContent = timeLeft;
      
      if (timeLeft <= 0) {
        clearInterval(countdown);
        window.location.href = 'dashboard.php';
      }
    }, 1000);
  </script>
  <?php endif; ?>

  <script>
  function checkOrderStatus() {
      fetch('check_order_status.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const currentStatus = '<?php echo $order_status['status']; ?>';
                  
                  // Check if status has changed
                  if (data.status !== currentStatus) {
                      // Status has changed, reload the page to show updated status
                      location.reload();
                  }
                  
                  // Show notifications for specific status changes
                  if (data.status === 'Ready') {
                      showNotification('Your order is ready to be claimed!');
                  } else if (data.status === 'Preparing') {
                      showNotification('Your order is now being prepared!');
                  }
                  
                  // Show notification for advance orders
                  if (data.notification) {
                      showNotification(data.notification);
                  }
              }
          })
          .catch(error => console.error('Error checking order status:', error));
  }

  const style = document.createElement('style');
  style.textContent = `
      @keyframes slideDown {
          from {
              transform: translate(-50%, -100%);
              opacity: 0;
          }
          to {
              transform: translate(-50%, 0);
              opacity: 1;
          }
      }
      
      @keyframes slideUp {
          from {
              transform: translate(-50%, 0);
              opacity: 1;
          }
          to {
              transform: translate(-50%, -100%);
              opacity: 0;
          }
      }
  `;
  document.head.appendChild(style);

  // Check status every 3 seconds
  setInterval(checkOrderStatus, 3000);
  
  // Also check when the page becomes visible (user switches back to tab)
  document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
          checkOrderStatus();
      }
  });
  </script>
</body>
</html>
