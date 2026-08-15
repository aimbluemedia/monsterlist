<div class="wrap dash">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div>
    <h1>Monthly article</h1>
    <div class="card card-pad" style="background:var(--accent-soft);border-color:var(--accent)">
      <h3 style="margin-top:0">This one is on the Featured plan</h3>
      <p class="mute">Featured members get an article written for them every month. We publish it, post it out
        across our own <?= e(implode(', ', article_channels())) ?> channels, and promote it to each of theirs —
        no tokens spent, no posts to write.</p>
      <p class="mute">On <?= e($plan['label']) ?> you have the tokens to promote your own posts whenever you like.</p>
      <a class="btn btn-primary" href="/pricing">See plans</a>
      <a class="btn btn-ghost" href="/account/promotions">Promote something now</a>
    </div>
  </div>
</div>
