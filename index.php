<?php
// ============================================================
// AeroGlide — index.php
// Static replica of the AeroGlide travel mockup.
// Requires only a PHP-capable web server (or rename to .html —
// the page works without any server-side logic).
// ============================================================

$CDN = "https://id-preview--9af213b0-9d68-412c-a382-7cf2bea5e25d.lovable.app";

$img = [
  "logo"      => "$CDN/__l5e/assets-v1/5744bbcd-87b5-4be7-8858-9068efd6d523/logo.png",
  "plane"     => "$CDN/__l5e/assets-v1/c552267a-828c-430d-bf29-458381303b30/plane.png",
  "about"     => "$CDN/__l5e/assets-v1/02e80780-57bb-46b7-a689-6d6fdd76cfcf/about.jpg",
];

$heroSlides = [
  "$CDN/__l5e/assets-v1/bdeb2bde-4d4b-429b-bc6c-007d1d1aee54/hero1.jpg",
  "$CDN/__l5e/assets-v1/279bdf77-8000-457d-ad5c-f3553dd3b7eb/hero2.jpg",
  "$CDN/__l5e/assets-v1/169745bc-ba35-4aa0-a4ba-21569bbaa7f3/hero3.jpg",
];

$deals = [
  [
    "city"  => "Rio de Janeiro, Brazil",
    "image" => "$CDN/__l5e/assets-v1/52931255-9b92-456e-a31e-cf599d77fb3d/rio.jpg",
    "fares" => [
      ["Lite Escape", "Flight only", "₱18,999"],
      ["Smart Saver", "Flight + checked baggage", "₱21,999"],
      ["Budget Employer", "Flight + baggage + meal", "₱25,999"],
    ],
  ],
  [
    "city"  => "Santorini, Greece",
    "image" => "$CDN/__l5e/assets-v1/be2828f0-31c3-431b-b0d7-f031c7f06130/santorini.jpg",
    "fares" => [
      ["Lite Escape", "Flight only", "₱22,499"],
      ["Smart Saver", "Flight + checked baggage", "₱26,999"],
      ["Budget Employer", "Flight + baggage + meal", "₱31,999"],
    ],
  ],
  [
    "city"  => "Cancún, Mexico",
    "image" => "$CDN/__l5e/assets-v1/680da9c1-c118-4c5a-9e16-4dc2fbdb776c/cancun.jpg",
    "fares" => [
      ["Lite Escape", "Flight only", "₱19,499"],
      ["Smart Saver", "Flight + checked baggage", "₱23,999"],
      ["Budget Employer", "Flight + baggage + meal", "₱28,999"],
    ],
  ],
];

$destinations = [
  ["Taj Mahal", "India", "$CDN/__l5e/assets-v1/430d3601-bfe6-4b3f-b4f2-f5bb99da7e1e/taj.jpg"],
  ["Disneyland", "Hong kong", "$CDN/__l5e/assets-v1/c7c14fb9-0dd3-4b7d-9f1f-9ddbbe8189e5/disney.jpg"],
  ["Dubai", "United Arab Emirates", "$CDN/__l5e/assets-v1/c0bf3b08-51d3-4384-8c27-77d1999e4326/dubai.jpg"],
  ["New York", "United States", "$CDN/__l5e/assets-v1/8f13f16b-693c-41ee-86f1-e51633474f5d/newyork.jpg"],
  ["Shanghai", "China", "$CDN/__l5e/assets-v1/8f13f16b-693c-41ee-86f1-e51633474f5d/shanghai.jpg"],
];
// fix shanghai url
$destinations[4][2] = "$CDN/__l5e/assets-v1/8f13f16b-693c-41ee-86f1-e51633474f5d/shanghai.jpg";

$reviews = [
  ["“Booking my trip with AeroGlide was surprisingly easy. The website is clean, fast, and the prices were very affordable. I'll definitely use AeroGlide again!”", "Noblesam Martizano"],
  ["“I really liked how simple it was to compare destinations and travel packages. The whole booking experience felt smooth and hassle-free.”", "Bianca Briel Cruz"],
  ["“AeroGlide made planning my vacation much easier. The deals were great, and I loved how straightforward the website was to use.”", "Dane Nicolle"],
];

$footerCols = [
  ["Travel", ["Asia", "Europe", "Australia", "America"]],
  ["Company", ["About Us", "Packages", "Contact Us"]],
  ["Extra Links", ["Customer Support", "Terms and Conditions", "Privacy Policy"]],
];

function initials(string $name): string {
  $out = "";
  foreach (explode(" ", $name) as $part) { $out .= strtoupper($part[0] ?? ""); }
  return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AeroGlide — Book Smarter, Travel Further</title>
  <meta name="description" content="AeroGlide makes air travel simple and affordable. Compare exclusive flight deals, popular destinations and travel packages, then book in a few clicks." />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="AeroGlide — Book Smarter, Travel Further" />
  <meta property="og:description" content="AeroGlide makes air travel simple and affordable. Compare exclusive flight deals, popular destinations and travel packages." />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" href="<?= $img['logo'] ?>" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <!-- ============ NAV ============ -->
  <header class="nav">
    <a href="/" class="brand">
      <img src="<?= $img['logo'] ?>" alt="AeroGlide logo" width="40" height="40" />
      <span>AeroGlide</span>
    </a>
    <nav class="nav-links">
      <a href="#" class="active">Home</a>
      <a href="#">Flights</a>
      <a href="#">Package</a>
      <a href="#">Support</a>
    </nav>
    <div class="nav-actions">
      <label class="search-pill">
        <input type="search" placeholder="SEARCH" aria-label="Search" />
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </label>
      <a href="#" class="signup">
        Sign up
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.7c1.6-2.6 6.4-2.6 10 0"/></svg>
      </a>
    </div>
  </header>

  <!-- ============ HERO ============ -->
  <section class="hero clouds">
    <div class="hero-inner">
      <h1>Book smarter,<br />Travel further</h1>
      <p class="hero-sub">Your next destination is just a click away</p>

      <img class="hero-plane" src="<?= $img['plane'] ?>" alt="AeroGlide aircraft in flight" width="1536" height="1024" />

      <div class="hero-slides" id="heroSlides">
        <?php foreach ($heroSlides as $i => $src): ?>
          <img src="<?= $src ?>" alt="Travel destination" loading="lazy"
               class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" />
        <?php endforeach; ?>
      </div>

      <div class="hero-pager">
        <button class="pager-btn" id="prevSlide" aria-label="Previous slide">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <span id="slideCount">1/06</span>
        <button class="pager-btn" id="nextSlide" aria-label="Next slide">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>

      <a href="#deals" class="btn-dark">Book a trip now</a>
    </div>
  </section>

  <!-- ============ BOOKING BAR ============ -->
  <section class="booking">
    <div class="booking-panel">
      <div class="booking-tabs">
        <button class="tab">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
          Flights
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <button class="tab bordered">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Adult
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <button class="tab bordered">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
          Economy
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="m6 9 6 6 6-6"/></svg>
        </button>
      </div>

      <div class="booking-body">
        <div class="trip-type">
          <label class="radio">
            <input type="radio" name="trip" value="two" checked />
            <span class="radio-dot"></span> Two way
          </label>
          <label class="radio">
            <input type="radio" name="trip" value="one" />
            <span class="radio-dot"></span> One way
          </label>
        </div>

        <div class="booking-fields">
          <div class="field">
            <p class="field-label">Destinations ↓</p>
            <input placeholder="Where are you going?" aria-label="Destinations" />
          </div>
          <div class="field">
            <p class="field-label">Check In</p>
            <div class="field-input">
              <input placeholder="Choose dates" aria-label="Check In" />
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
          </div>
          <div class="field">
            <p class="field-label">Check Out</p>
            <div class="field-input">
              <input placeholder="Choose dates" aria-label="Check Out" />
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
          </div>
          <div class="field">
            <p class="field-label">Guest ↓</p>
            <input placeholder="Add Guests" aria-label="Guest" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ DEALS ============ -->
  <section id="deals" class="deals">
    <h2>Exclusive AeroGlide Deals</h2>
    <p class="section-sub">Premium Travel Experiences at Unbeatable Rates.</p>

    <div class="deals-grid">
      <?php foreach ($deals as $di => $d): ?>
        <article class="card deal-card">
          <img src="<?= $d['image'] ?>" alt="<?= $d['city'] ?>" loading="lazy" class="deal-img" />
          <div class="deal-body">
            <h3><?= $d['city'] ?></h3>
            <ul class="fare-list">
              <?php foreach ($d['fares'] as $fi => $f): ?>
                <li class="fare">
                  <button class="fare-radio<?= $fi === 0 ? ' is-selected' : '' ?>"
                          data-deal="<?= $di ?>" data-fare="<?= $fi ?>"
                          aria-label="Select <?= $f[0] ?>"></button>
                  <span class="fare-info">
                    <span class="fare-name"><?= $f[0] ?></span>
                    <span class="fare-note"><?= $f[1] ?></span>
                  </span>
                  <span class="fare-price"><?= $f[2] ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="deal-actions">
              <a href="#" class="btn-sky">Learn more</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ============ DESTINATIONS ============ -->
  <section class="destinations clouds">
    <h2>Popular Destinations</h2>
    <div class="dest-grid">
      <?php foreach ($destinations as $d): ?>
        <article class="card dest-card">
          <img src="<?= $d[2] ?>" alt="<?= $d[0] ?>, <?= $d[1] ?>" loading="lazy" />
          <div class="dest-body">
            <h3><?= $d[0] ?></h3>
            <p><?= $d[1] ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ============ ABOUT ============ -->
  <section class="about">
    <h2>About us</h2>
    <div class="about-grid">
      <p>
        AeroGlide is a modern airline created to make air travel simple, affordable, and enjoyable
        for everyone. We connect travelers to exciting destinations around the world while
        providing convenient booking options, competitive fares, and a smooth travel experience
        from start to finish. We believe that traveling should be more than simply getting from one
        place to another. It should be about discovering new places, experiencing different
        cultures, and creating unforgettable memories. That is why AeroGlide is committed to
        providing reliable service while keeping travel accessible and budget-friendly.
      </p>
      <img src="<?= $img['about'] ?>" alt="Aircraft landing at sunset" loading="lazy" />
    </div>
  </section>

  <!-- ============ CLIENTS ============ -->
  <section class="clients clouds">
    <h2>AeroGlide Clients</h2>
    <div class="clients-grid">
      <?php foreach ($reviews as $r): ?>
        <figure class="card review-card">
          <div class="stars" aria-label="5 out of 5 stars">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?php endfor; ?>
          </div>
          <blockquote><?= $r[0] ?></blockquote>
          <figcaption>
            <span class="avatar"><?= initials($r[1]) ?></span>
            <span class="reviewer"><?= $r[1] ?></span>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="footer">
    <div class="footer-card card">
      <div class="footer-brand">
        <div class="brand-lg">
          <img src="<?= $img['logo'] ?>" alt="AeroGlide logo" loading="lazy" width="80" height="80" />
          <span>AeroGlide</span>
        </div>
        <div class="socials">
          <span>Follow</span>
          <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
          <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect width="20" height="20" x="2" y="2" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg></a>
          <a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg></a>
        </div>
      </div>
      <div class="footer-cols">
        <?php foreach ($footerCols as $c): ?>
          <div>
            <h4><?= $c[0] ?></h4>
            <ul>
              <?php foreach ($c[1] as $item): ?>
                <li><a href="#"><?= $item ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </footer>

  <script>
    // Hero carousel
    (function () {
      const slides = document.querySelectorAll(".hero-slide");
      const count = document.getElementById("slideCount");
      let current = 0;
      function show(i) {
        current = (i + slides.length) % slides.length;
        slides.forEach((s, idx) => s.classList.toggle("is-active", idx === current));
        count.textContent = (current + 1) + "/06";
      }
      document.getElementById("prevSlide").addEventListener("click", () => show(current - 1));
      document.getElementById("nextSlide").addEventListener("click", () => show(current + 1));
    })();

    // Deal fare selection
    (function () {
      document.querySelectorAll(".fare-radio").forEach((btn) => {
        btn.addEventListener("click", () => {
          const deal = btn.dataset.deal;
          document
            .querySelectorAll('.fare-radio[data-deal="' + deal + '"]')
            .forEach((b) => b.classList.toggle("is-selected", b === btn));
        });
      });
    })();
  </script>
</body>
</html>
