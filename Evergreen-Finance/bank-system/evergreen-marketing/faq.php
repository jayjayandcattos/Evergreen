<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAQs - Evergreen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Kulim+Park:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
        :root{
          --green-900:#003631;
          --green-700:#0a6b62;
          --accent:#F1B24A;
          --card-bg: rgba(255,255,255,0.95);
        }

        *{
          box-sizing:border-box;
          margin: 0;
          padding: 0;
        }
        html,body{
          height:100%;
          font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        /* page background */
        body{
          background: linear-gradient(180deg, #00483f 0%, #0b9f8e 100%);
          -webkit-font-smoothing:antialiased;
          color: #fff;
          padding: 28px 32px;
          min-height: 100vh;
        }

        nav{
          display:flex;
          align-items:center;
          gap:12px;
          position:relative;
          padding: 6px 12px;
        }

        nav img { 
          width:52px; 
          height:48px; 
          border-radius:50%; 
        }

        .brand a {
          font-family: "Kulim Park", sans-serif;
          font-weight: bold;
          color: #FFFFFF;
          font-size:20px;
          text-decoration: none;
        }
        .motto a { 
          font-family: Inter; 
          font-size:12px; 
          color: rgba(230,255,249,0.9); 
          margin-top:2px; 
          text-decoration: none; 
        }

        /* main layout */
        .container{
          display:flex;
          padding: 28px;
          justify-content: space-between;
          align-items: center;
          width: 100%;
          min-height: 80vh;
          gap: 3rem;
        }

        /* left column (big title + text) */
        .hero-left{
          flex:1;
          min-width:320px;
          padding-top:24px;
          align-self: center;
        }
        .hero-left h1{
          font-family: "Kulim Park", sans-serif;
          font-size:60px;
          line-height:1.02;
          margin:0 0 18px 0;
          color:#ffffff; 
          text-transform:uppercase;
          letter-spacing:1px;
        }
        .hero-left p{
          margin:0;
          max-width:520px;
          color: rgba(230,255,249,0.9);
          font-size:16px;
          line-height:1.6;
        }

        /* right column (card) */
        .faq-card{
          width:1020px;
          background: rgba(255, 255, 255, 0.7);
          color: #003631;
          border-radius:12px;
          padding:26px;
          box-shadow: 0 18px 40px rgba(3,20,18,0.35);
          border: 1px solid rgba(0,54,49,0.06);
          align-self:center;
          max-width: 100%;
        }

        .faq-card h3{
          margin:0 0 14px 0;
          font-family: "Kulim Park", sans-serif;
          color:var(--green-900);
          font-size:20px;
        }
        .faq-list{ 
          list-style:none; 
          padding:0; 
          margin:0; 
        }
        .faq-item{
          padding:14px 0;
          border-bottom:1px solid rgba(0,0,0,0.06);
          cursor:default;
        }
        .faq-q{
          display:flex;
          justify-content:space-between;
          gap:12px;
          align-items:center;
          font-weight:600;
          font-size:14px;
          color:var(--green-900);
        }
        .faq-a{
          margin-top:8px;
          font-size:13px;
          color:#36524e;
          line-height:1.5;
          max-height:0;
          overflow:hidden;
          transition: max-height 260ms ease, opacity 240ms ease, transform 260ms ease;
          opacity:0;
          transform:translateY(-4px);
        }
        .faq-item.open .faq-a{
          opacity:1;
          transform:translateY(0);
          max-height:240px;
        }
        .faq-toggle{
          background:transparent;
          border:none;
          color:var(--green-700);
          font-weight:700;
          font-size:16px;
          cursor:pointer;
          padding:6px;
          border-radius:6px;
          transition: transform 160ms ease;
          min-width: 28px;
          flex-shrink: 0;
        }
        .faq-toggle:hover {
          background: rgba(0,54,49,0.05);
        }
        .faq-toggle:active{ 
          transform:scale(0.98); 
        }

        /* Responsive Design */
        @media (max-width: 968px) {
          body {
            padding: 20px 24px;
          }

          nav {
            padding: 4px 8px;
            gap: 10px;
          }

          nav img {
            width: 44px;
            height: 40px;
          }

          .brand a {
            font-size: 18px;
          }

          .motto a {
            font-size: 11px;
          }

          .container {
            flex-direction: column;
            gap: 2rem;
            padding: 20px;
            min-height: auto;
          }

          .hero-left {
            padding-top: 10px;
            text-align: center;
          }

          .hero-left h1 {
            font-size: 42px;
          }

          .hero-left p {
            max-width: 100%;
            font-size: 15px;
          }

          .faq-card {
            width: 100%;
            max-width: 100%;
            padding: 24px;
          }

          .faq-card h3 {
            font-size: 18px;
          }

          .faq-q {
            font-size: 13px;
          }

          .faq-a {
            font-size: 12px;
          }
        }

        @media (max-width: 640px) {
          body {
            padding: 15px 18px;
          }

          nav {
            flex-wrap: wrap;
            gap: 8px;
          }

          nav img {
            width: 38px;
            height: 35px;
          }

          .brand a {
            font-size: 16px;
          }

          .motto {
            display: none;
          }

          .container {
            padding: 15px;
            gap: 1.5rem;
          }

          .hero-left {
            min-width: auto;
            width: 100%;
          }

          .hero-left h1 {
            font-size: 32px;
            margin-bottom: 12px;
            line-height: 1.1;
          }

          .hero-left p {
            font-size: 14px;
            line-height: 1.5;
          }

          .faq-card {
            padding: 20px;
            border-radius: 10px;
          }

          .faq-card h3 {
            font-size: 16px;
            margin-bottom: 12px;
          }

          .faq-item {
            padding: 12px 0;
          }

          .faq-q {
            font-size: 12px;
            gap: 8px;
          }

          .faq-a {
            font-size: 11px;
            margin-top: 6px;
          }

          .faq-toggle {
            font-size: 14px;
            padding: 4px;
            min-width: 24px;
          }
        }

        @media (max-width: 480px) {
          body {
            padding: 12px 15px;
          }

          nav img {
            width: 34px;
            height: 32px;
          }

          .brand a {
            font-size: 14px;
          }

          .container {
            padding: 12px;
          }

          .hero-left h1 {
            font-size: 28px;
            margin-bottom: 10px;
          }

          .hero-left p {
            font-size: 13px;
          }

          .faq-card {
            padding: 18px;
          }

          .faq-card h3 {
            font-size: 15px;
          }

          .faq-item {
            padding: 10px 0;
          }

          .faq-q {
            font-size: 11px;
          }

          .faq-q span {
            line-height: 1.3;
          }

          .faq-a {
            font-size: 10px;
            line-height: 1.4;
          }

          .faq-toggle {
            font-size: 13px;
            min-width: 22px;
          }
        }

        @media (max-width: 360px) {
          .hero-left h1 {
            font-size: 24px;
          }

          .hero-left p {
            font-size: 12px;
          }

          .faq-card {
            padding: 15px;
          }

          .faq-card h3 {
            font-size: 14px;
          }

          .faq-q {
            font-size: 10px;
          }

          .faq-a {
            font-size: 9px;
          }
        }

        /* Landscape Orientation */
        @media (max-height: 600px) and (orientation: landscape) {
          body {
            padding: 15px 20px;
          }

          .container {
            flex-direction: row;
            min-height: auto;
            padding: 15px;
          }

          .hero-left h1 {
            font-size: 36px;
            margin-bottom: 8px;
          }

          .hero-left p {
            font-size: 13px;
          }

          .faq-card {
            max-width: 50%;
            padding: 18px;
          }
        }

        /* Very small landscape phones */
        @media (max-width: 640px) and (orientation: landscape) {
          .container {
            flex-direction: column;
          }

          .hero-left h1 {
            font-size: 28px;
          }

          .faq-card {
            max-width: 100%;
          }
        }
  </style>
</head>
<body>
  <nav>
      <a href="viewingpage.php">
        <img src="images/icon.png" alt="Evergreen logo"> 
      </a>
    <div>
      <div class="brand">
        <a href="viewingpage.php">EVERGREEN</a>
      </div>
      <div class="motto">
        <a href="viewingpage.php">Secure, Invest, Achieve</a></div>
    </div>
  </nav>

  <main class="container" role="main">
    <section class="hero-left" aria-labelledby="faq-title">
      <h1 id="faq-title">Frequently<br>Asked Questions</h1>
      <p>Find quick answers to common questions about our banking services, accounts, and digital tools.</p>
    </section>

    <aside class="faq-card" aria-label="FAQ list">
      <h3>Common questions</h3>

      <ul class="faq-list" id="faqList">
        <li class="faq-item">
          <div class="faq-q">
            <span>How long does it take to get loan approval?</span>
            <button class="faq-toggle" aria-expanded="false">v</button>
          </div>
          <div class="faq-a">Loan approval usually takes 1–3 business days after submitting all required documents. You’ll receive a notification once your application has been reviewed.</div>
        </li>

        <li class="faq-item">
          <div class="faq-q">
            <span>Is my personal information safe when applying online?</span>
            <button class="faq-toggle" aria-expanded="false">v</button>
          </div>
          <div class="faq-a">Yes. We use industry-standard encryption and strict access controls to keep your information secure. Never share your password or verification codes.</div>
        </li>

        <li class="faq-item">
          <div class="faq-q">
            <span>How do I contact customer support for loan inquiries?</span>
            <button class="faq-toggle" aria-expanded="false">v</button>
          </div>
          <div class="faq-a">You can call our support line at 1-800-EVERGREEN or email loans@evergreenbank.com. Support is available Monday–Friday, 8am–6pm.</div>
        </li>

        <li class="faq-item">
          <div class="faq-q">
            <span>Do I need to create an account to apply for a loan?</span>
            <button class="faq-toggle" aria-expanded="false">v</button>
          </div>
          <div class="faq-a">You can start an application without an account, but creating one lets you save progress and track your application status.</div>
        </li>

        <li class="faq-item">
          <div class="faq-q">
            <span>What fees are associated with account maintenance?</span>
            <button class="faq-toggle" aria-expanded="false">v</button>
          </div>
          <div class="faq-a">Fees vary by account type. Visit our Fees & Pricing page or contact support for details specific to your account.</div>
        </li>
      </ul>
    </aside>
  </main>

  <script>
    // simple accordion behavior
    document.querySelectorAll('.faq-toggle').forEach(btn=>{
      btn.addEventListener('click', ()=> {
        const li = btn.closest('.faq-item');
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        // close other items (optional — keep single open like image)
        document.querySelectorAll('.faq-item.open').forEach(openEl=>{
          if (openEl !== li) {
            openEl.classList.remove('open');
            openEl.querySelector('.faq-toggle').textContent = 'v';
            openEl.querySelector('.faq-toggle').setAttribute('aria-expanded','false');
          }
        });
        if (expanded) {
          li.classList.remove('open');
          btn.textContent = 'v';
          btn.setAttribute('aria-expanded','false');
        } else {
          li.classList.add('open');
          btn.textContent = '−';
          btn.setAttribute('aria-expanded','true');
        }
      });
    });
  </script>
</body>
</html>