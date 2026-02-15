<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ShowGo - Movie Booking</title>
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
          <!-- <li><a href="user-login.php">Login</a></li> -->
          <li><a href="admin-login.php">Admin</a></li> 
        </ul>
      </nav>
  </header>
  <div class="slider-container">
    <div class="slider">
      <div class="slide active" style="background-image: url('images/slider1.jpg');">
        <div class="slide-content">
          <h1>Avengers EndGame</h1>
          <a class="btn" href="user-login.php">Buy Now</a>
        </div>
      </div>
      <div class="slide" style="background-image: url('images/slider2.jpeg');">
        <div class="slide-content">
          <h1>Avatar: Fire and Ice</h1>
          <a class="btn" href="user-login.php">Buy Now</a>
        </div>
      </div>
      <div class="slide" style="background-image: url('images/slider3.jpg');">
        <div class="slide-content">
          <h1>Bhool Bhulaiyaa 3</h1>
          <a class="btn" href="user-login.php">Buy Now</a>
        </div>
      </div>
    </div>
    <div class="prev arrow">&#10094;</div>
    <div class="next arrow">&#10095;</div>
    <div class="dots">
      <span class="dot active-dot"></span>
      <span class="dot"></span>
      <span class="dot"></span>
    </div>
  </div>
  <h2>Now Showing</h2>
  <div class="movies-container">
    <div class="movies-card">
      <div class="posters">
        <a href="movie1.php#avenger">
        <img src="images/movies1.jpeg" alt="Avengers EndGame">
        </a>
      </div>
      <h3>Avengers: EndGame</h3>
      <p>Duration: 120 min</p>
      <div class="buttons">
        <div class="timings">
          <a class="timebutton1" href="user-login.php?movie_id=1&movie_title=Avengers: EndGame&show_time=8:00 AM">8:00 AM</a>
          <a class="timebutton2" href="user-login.php?movie_id=1&movie_title=Avengers: EndGame&show_time=3:30 PM">3:30 PM</a>
          <a class="timebutton3" href="user-login.php?movie_id=1&movie_title=Avengers: EndGame&show_time=10:00 PM">10:00 PM</a>
        </div>
      </div>
    </div>
    <div class="movies-card">
      <div class="posters">
        <a href="movie2.php#avatar">
        <img src="images/movies2.jpeg" alt="Avatar Fire and Ash">
        </a>
      </div>
      <h3>Avatar: Fire and Ash</h3>
      <p>Duration: 130 min</p>
      <div class="buttons">
        <div class="timings">
          <a class="timebutton3" href="user-login.php?movie_id=2&movie_title=Avatar: Fire and Ash&show_time=10:00 AM">10:00 AM</a>
          <a class="timebutton1" href="user-login.php?movie_id=2&movie_title=Avatar: Fire and Ash&show_time=1:00 PM">1:00 PM</a>
          <a class="timebutton2" href="user-login.php?movie_id=2&movie_title=Avatar: Fire and Ash&show_time=4:00 PM">4:00 PM</a>
        </div>
      </div>
    </div>
    <div class="movies-card">
      <div class="posters">
        <a href="movie3.php#bhoolbhulaiyaa">
        <img src="images/movies3.jpeg" alt="Bhool Bhulaiyaa 3">
        </a>
      </div>
      <h3>Bhool Bhulaiyaa 3</h3>
      <p>Duration: 115 min</p>
      <div class="buttons">
        <div class="timings">
          <a class="timebutton3" href="user-login.php?movie_id=3&movie_title=Bhool Bhulaiyaa 3&show_time=11:45 AM">11:45 AM</a>
          <a class="timebutton2" href="user-login.php?movie_id=3&movie_title=Bhool Bhulaiyaa 3&show_time=2:00 PM">2:00 PM</a>
          <a class="timebutton1" href="user-login.php?movie_id=3&movie_title=Bhool Bhulaiyaa 3&show_time=7:30 PM">7:30 PM</a>
        </div>
      </div>
    </div>
  </div>
  <h2>Coming Soon</h2>
  <div class="coming-container">
    <div class="coming-card">
      <div class="poster">
        <img src="images/movies4.jpg" alt="Anaconda">
      </div>
      <div class="desc">
        <h3>Anaconda</h3>
        <p>Anaconda is an upcoming American action comedy horror film serving as a meta-reboot of the 1997 film Anaconda and stars Paul Rudd and Jack Black. It was written by Gormican and Etten, and directed by Gormican. The film also features Steve Zahn, Thandiwe Newton, Daniela Melchior, and Selton Mello.</p>
        <p>Release Date: 2025-01-10</p>
        <p>Runtime: 140 min</p>
      </div>
    </div>
    <div class="coming-card">
      <div class="poster">
      <img src="images/movies5.jpeg" alt="Burn the Silence">
      </div>
      <div class="desc">
        <h3>Burn the Silence</h3>
        <p>Break the Silence: The Movie follows the band on and off stage for 14 months during their Love Yourself World Tour and it further showcases their vulnerable emotional side, illustrating how they separate themselves from their stage personas. It also features stock footage of the band.</p>
        <p>Release Date: 2025-03-04</p>
        <p>Runtime: 150 min</p>
      </div>
    </div>
  </div>
  <footer>Copyright ©2025 ShowGo</footer>
  <div id="offerModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <img src="images/popup.png" alt="Special Offer" class="popup-image">
    </div>
  </div>
</body>
<script src="slider.js"></script>
<script src="popup.js"></script>  

</html>
