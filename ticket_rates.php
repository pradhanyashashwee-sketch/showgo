<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Rates</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header>
    <a href="index.php" class="logo-link">
    <div class="logo"><strong>ShowGO</strong></div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="booking.php">Movies</a></li>
        <li><a href="ticket_rates.php">Ticket Rate</a></li>
        <!-- <li><a href="user-login.php">Login</a></li>-->
        <li><a href="admin-login.php">Admin</a></li> 
      </ul>
    </nav>
  </header>
  <div class="price-container">
    <h2>Ticket Prices</h2>
    <table class="price-table">
      <tr>
        <th>Show Type</th>
        <th>Days</th>
        <th>Price (Rs.)</th>
      </tr>
      <tr>
        <td>Morning Show</td>
        <td>Weekends (Fri-Sun)</td>
        <td>200</td>
      </tr>
      <tr>
        <td>Morning Show</td>
        <td>Weekdays (Mon-Tue)</td>
        <td>150</td>
      </tr>
      <tr>
        <td>Regular Show</td>
        <td>Weekends (Fri-Sun)</td>
        <td>400</td>
      </tr>
      <tr>
        <td>Regular Show</td>
        <td>Weekdays (Mon-Tue)</td>
        <td>300</td>
      </tr>
      <tr>
        <td colspan="2">
          Wednesday / Thursday <br>
          <small>*Offer not applicable for new release movies</small>
        </td>
        <td>175</td>
      </tr>
      <tr>
        <td colspan="2">3D Show</td>
        <td>225</td>
      </tr>
    </table>
  </div>
  <footer>Copyright ©2025 ShowGo</footer>
</body>
</html>