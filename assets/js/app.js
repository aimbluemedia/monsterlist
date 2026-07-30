// MonsterList — light progressive enhancement. All pages work without JS.
document.addEventListener('DOMContentLoaded', function () {
  // Confirm dangerous actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // AI fill on the new-listing form
  var aiBtn = document.getElementById('ai-fill-btn');
  if (aiBtn) {
    var aiUrl = document.getElementById('ai-url');
    var aiStatus = document.getElementById('ai-status');
    var form = document.querySelector('form.card');

    var setField = function (name, value) {
      if (value === undefined || value === null || value === '') return;
      var el = form.querySelector('[name="' + name + '"]');
      if (!el) return;
      el.value = value;
      el.style.background = '#fef9e7'; // gentle highlight: review AI-filled values
    };

    var runFill = function () {
      var url = aiUrl.value.trim();
      if (!url) { aiStatus.textContent = 'Enter your website address first.'; return; }
      aiBtn.disabled = true;
      aiStatus.textContent = 'Reading your website… this usually takes 15–60 seconds.';

      var data = new FormData();
      data.append('_csrf', aiUrl.getAttribute('data-csrf'));
      data.append('url', url);

      fetch('/account/listings/autofill', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          aiBtn.disabled = false;
          if (!res.ok) { aiStatus.textContent = res.error || 'Something went wrong.'; return; }
          var f = res.fields;
          setField('name', f.name);
          setField('tagline', f.tagline);
          setField('description', f.description);
          setField('category_id', f.category_id);
          setField('country', f.country);
          setField('region', f.region);
          setField('city', f.city);
          setField('address', f.address);
          setField('phone', f.phone);
          setField('email', f.email);
          setField('website', f.website);
          setField('founded', f.founded);
          if (f.social) {
            Object.keys(f.social).forEach(function (net) { setField('social_' + net, f.social[net]); });
          }
          aiStatus.textContent = '✓ Done! Highlighted fields were filled by AI — please review them, then submit.';
        })
        .catch(function () {
          aiBtn.disabled = false;
          aiStatus.textContent = 'Network error — please try again.';
        });
    };
    aiBtn.addEventListener('click', runFill);
    aiUrl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runFill(); } });
  }

  // Homepage graphic: count the numbers up once, when it scrolls into view.
  // The final value is already in the markup, so no-JS and reduced-motion
  // visitors simply see it sitting there.
  var stage = document.getElementById('ml-stage');
  var slowMo = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (stage && !slowMo && window.IntersectionObserver) {
    var targets = stage.querySelectorAll('[data-count]');

    var runCount = function () {
      targets.forEach(function (el) {
        var end = parseInt(el.getAttribute('data-count'), 10);
        var suffix = el.getAttribute('data-suffix') || '';
        if (isNaN(end)) return;
        var dur = 1100 + Math.random() * 500;
        var t0 = null;
        var tick = function (now) {
          if (t0 === null) t0 = now;
          var p = Math.min((now - t0) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);           // ease-out cubic
          el.textContent = Math.round(end * eased).toLocaleString() + suffix;
          if (p < 1) requestAnimationFrame(tick);
        };
        el.textContent = '0' + suffix;
        requestAnimationFrame(tick);
      });
    };

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        io.disconnect();
        runCount();
      });
    }, { threshold: 0.25 });
    io.observe(stage);
  }

  // Promotion-engine demo: pick an example, hit boost, watch the preview move.
  // Everything here is illustrative — it never calls the server.
  var peBoost = document.getElementById('pe-boost');
  if (peBoost) {
    var DEMOS = {
      yt:   { icon: 'ml-yt',   name: 'YouTube',   grad: 'linear-gradient(135deg,#2563eb,#7c3aed)',
              url: 'https://youtube.com/watch?v=your-latest-video',
              title: 'How we doubled our bookings in 60 days', stats: [128, 34, 19] },
      blog: { icon: 'ml-blog', name: 'Blog post',  grad: 'linear-gradient(135deg,#0ca678,#2563eb)',
              url: 'https://yoursite.com/blog/questions-customers-ask',
              title: 'The 7 questions every customer asks before buying', stats: [96, 41, 12] },
      ig:   { icon: 'ml-ig',   name: 'Instagram', grad: 'linear-gradient(135deg,#db2777,#7c3aed)',
              url: 'https://instagram.com/p/your-latest-post',
              title: 'Behind the scenes: our new spring collection', stats: [154, 28, 47] },
      prod: { icon: 'ml-prod', name: 'Product',   grad: 'linear-gradient(135deg,#f59e0b,#db2777)',
              url: 'https://yourstore.com/products/oak-dining-table',
              title: 'Handmade oak dining table — now in three finishes', stats: [83, 52, 16] }
    };

    var peUrl    = document.getElementById('pe-url');
    var peTitle  = document.getElementById('pe-title');
    var peThumb  = document.getElementById('pe-thumb');
    var peStatus = document.getElementById('pe-status');
    var peFill   = document.getElementById('pe-bar-fill');
    var peFaces  = document.getElementById('pe-faces');
    var peFacesT = document.getElementById('pe-faces-text');
    var peNums   = document.querySelectorAll('#pe-card [data-boost]');
    var peSlow   = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var peRun    = null;

    var setUse = function (id, symbol) {
      var el = document.getElementById(id);
      if (el) el.setAttribute('href', '#' + symbol);
    };

    var showDemo = function (key) {
      var d = DEMOS[key];
      if (!d) return;
      peUrl.value = d.url;
      peTitle.textContent = d.title;
      peThumb.style.background = d.grad;
      document.getElementById('pe-badge-text').textContent = d.name;
      setUse('pe-badge-icon', d.icon);
      setUse('pe-thumb-icon', d.icon);
      peNums.forEach(function (n, i) { n.setAttribute('data-boost', d.stats[i]); });
    };

    var reset = function () {
      if (peRun) { cancelAnimationFrame(peRun); peRun = null; }
      peNums.forEach(function (n) { n.textContent = '0'; });
      peFill.style.width = '0';
      peFaces.querySelectorAll('span').forEach(function (f) { f.classList.remove('in'); });
      peFacesT.textContent = 'members ready to boost';
      peStatus.textContent = 'Ready';
      peStatus.classList.remove('live');
    };

    var boost = function () {
      reset();
      peStatus.textContent = 'Boosting';
      peStatus.classList.add('live');

      var faces = peFaces.querySelectorAll('span');
      var dur = peSlow ? 0 : 1900;

      if (peSlow) {                                  // no animation: land on the end state
        peNums.forEach(function (n) { n.textContent = n.getAttribute('data-boost'); });
        peFill.style.width = '100%';
        faces.forEach(function (f) { f.classList.add('in'); });
        peFacesT.textContent = 'members boosted this';
        peStatus.textContent = 'Boosted';
        peStatus.classList.remove('live');
        return;
      }

      var t0 = null;
      var tick = function (now) {
        if (t0 === null) t0 = now;
        var p = Math.min((now - t0) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        peNums.forEach(function (n) {
          n.textContent = Math.round(parseInt(n.getAttribute('data-boost'), 10) * eased).toLocaleString();
        });
        peFill.style.width = (eased * 100) + '%';
        faces.forEach(function (f, i) { if (eased > (i + 1) / (faces.length + 1)) f.classList.add('in'); });
        if (p < 1) { peRun = requestAnimationFrame(tick); }
        else {
          peRun = null;
          peFacesT.textContent = 'members boosted this';
          peStatus.textContent = 'Boosted';
          peStatus.classList.remove('live');
        }
      };
      peRun = requestAnimationFrame(tick);
    };

    document.querySelectorAll('.pe-picks button').forEach(function (b) {
      b.addEventListener('click', function () {
        document.querySelectorAll('.pe-picks button').forEach(function (o) { o.classList.remove('on'); });
        b.classList.add('on');
        showDemo(b.getAttribute('data-demo'));
        boost();
      });
    });
    peBoost.addEventListener('click', boost);

    // Run once when the module first comes into view.
    if (window.IntersectionObserver) {
      var peIo = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          peIo.disconnect();
          boost();
        });
      }, { threshold: 0.3 });
      peIo.observe(document.getElementById('pe-card'));
    }
  }

  // Client-side filter box on /browse lists
  document.querySelectorAll('[data-filter]').forEach(function (input) {
    var target = document.querySelector(input.getAttribute('data-filter'));
    if (!target) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase();
      target.querySelectorAll('a').forEach(function (a) {
        a.style.display = a.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });
});
