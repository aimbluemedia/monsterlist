<?php
// One subcategory, add or edit. Expects $c (the category row) and $t — the
// subcategory being edited, or null to add a new one. Used twice on the page,
// because "add" and "edit" ask for exactly the same four things and having two
// forms that drift apart is how one of them ends up missing a field.
$isNew = empty($t);
$sel   = $isNew ? '' : (string)$t['schema_type'];
// Sorts count up in tens so a new row can be dropped between two existing ones
// without renumbering the rest.
$nextSort = 0;
foreach (($subs[$c['id']] ?? []) as $row) $nextSort = max($nextSort, (int)$row['sort']);
?>
<form method="post" class="ct-form <?= $isNew ? 'ct-form-new' : '' ?>"><?= csrf_field() ?>
  <input type="hidden" name="action" value="type_save">
  <input type="hidden" name="type_id" value="<?= $isNew ? 0 : (int)$t['id'] ?>">
  <input type="hidden" name="open" value="<?= e($c['id']) ?>">

  <?php if ($isNew): ?><h4 style="margin:18px 0 8px">Add a subcategory to <?= e($c['label']) ?></h4><?php endif; ?>

  <div class="ct-grid">
    <div>
      <label>Name *</label>
      <input type="text" name="label" value="<?= $isNew ? '' : e($t['label']) ?>"
             required maxlength="120" placeholder="e.g. Emergency plumber">
    </div>
    <div>
      <label>Category</label>
      <?php // Movable, so a trade filed on the wrong shelf is a dropdown rather
            // than a delete and a retype. ?>
      <select name="category_id">
        <?php foreach ($list as $opt): ?>
          <option value="<?= e($opt['id']) ?>" <?= ($isNew ? $c['id'] : $t['category_id']) === $opt['id'] ? 'selected' : '' ?>>
            <?= e($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Schema.org type</label>
      <select name="schema_type">
        <option value="">None — a plain LocalBusiness</option>
        <?php foreach (schema_type_catalog() as $type => $label): ?>
          <option value="<?= e($type) ?>" <?= $sel === $type ? 'selected' : '' ?>>
            <?= e($label) ?> — <?= e($type) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Order</label>
      <input type="number" name="sort" value="<?= $isNew ? $nextSort + 10 : (int)$t['sort'] ?>" step="10">
    </div>
    <div class="ct-go">
      <button class="btn btn-sm btn-primary"><?= $isNew ? 'Add subcategory' : 'Save' ?></button>
    </div>
  </div>
</form>
