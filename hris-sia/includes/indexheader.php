<head>
  <title>ELECT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Saira:wght@600&display=swap" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600&display=swap" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <div class="header">
    <div class="navbar">
      <div class="nav-content flex flex-col md:flex-row items-center justify-between gap-3 md:gap-0 px-4 sm:px-6">
        <div class="nav-left flex items-center w-full md:w-auto justify-center md:justify-start">
          <div class="title flex items-center gap-3">
            <a href="user/logout.php" title="Logout" class="hidden md:block">
              <img class="logo-nav dark w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14" src="assets/LOGO.png" alt="ELECT LOGO">
            </a>
            <h1 class="text-2xl sm:text-3xl" style="padding: 5px;">EVERGREEN</h1>
          </div>
        </div>

        <div class="nav-right !hidden md:!flex items-center w-full md:w-auto justify-center md:justify-end">
          <div class="date">
            <div class="date1 text-sm sm:text-base md:text-lg" id="currentDate">Date</div>
          </div>

          <!-- <div class="time">
            <div class="time1" id="currentTime">Time</div>
          </div> -->
        </div>

        <script>
          function updateDateTime() {
            const now = new Date();

            const options = {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            };
            const date = now.toLocaleDateString(undefined, options);
            const time = now.toLocaleTimeString();

        
            document.getElementById('currentDate').textContent = date;
            document.getElementById('currentTime').textContent = time;
          }

          setInterval(updateDateTime, 1000);
          updateDateTime();
        </script>
      </div>
    </div>
  </div>
</body>