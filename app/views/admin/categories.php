<?php require __DIR__ . '/_top.php'; ?>
<h1>Categories</h1>

<?php // Two levels on one page. The category is the shelf the site browses by;
      // the subcategories under it are the trades, and each carries the
      // Schema.org type that tells Google what the business actually is.
      //
      // A category folds open to show its own, rather than the page drawing all
      // sixteen lists at once — a hundred and eight rows in one column is not a
      // thing anybody reads. Which one is open is a link, not script, so it
      // survives the redirect after a save and can be linked to.
      $editingCat = $editing !== '' ? category_by_id($editing) : null;
?>

<?php if (!category_types_ready()): ?>
  <div class="flash flash-error">Subcategories need a database upgrade that has not been run yet.
    Until then the trade list stays as it was, built into the code, and cannot be edited here.
    Run <code>database/upgrade-all.sql</code> in phpMyAdmin.</div>
<?php endif; ?>

<div class="two-col">
  <div class="card card-pad">
    <table class="table cat-table">
      <tr><th>Icon</th><th>Label</th><th>ID</th><th>Listings</th><th>Subcategories</th><th></th></tr>
      <?php foreach ($list as $c): ?>
        <?php $mine = $subs[$c['id']] ?? []; $isOpen = $editing === $c['id']; ?>
        <tr id="cat-<?= e($c['id']) ?>" class="<?= $isOpen ? 'cat-open' : '' ?>">
          <td class="cat-icon"><?= e($c['icon']) ?></td>
          <td><strong><?= e($c['label']) ?></strong></td>
          <td class="mute"><?= e($c['id']) ?></td>
          <td><?= (int)$c['in_use'] ?></td>
          <td>
            <?php if (category_types_ready()): ?>
              <a href="<?= $isOpen ? '/superadmin/categories' : '/superadmin/categories?edit=' . e(urlencode($c['id'])) . '#cat-' . e($c['id']) ?>">
                <?= count($mine) ?: 'none' ?><?= $isOpen ? ' ▾' : ' ▸' ?>
              </a>
            <?php else: ?>
              <span class="mute"><?= count(schema_types()[$c['id']] ?? []) ?></span>
            <?php endif; ?>
          </td>
          <td class="cat-acts">
            <a class="btn btn-sm btn-ghost" href="/superadmin/categories?edit=<?= e(urlencode($c['id'])) ?>#cat-<?= e($c['id']) ?>">Edit</a>
            <form method="post"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($c['id']) ?>"><input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-danger"
                      data-confirm="Delete <?= e($c['label']) ?><?= $mine ? ' and its ' . count($mine) . ' subcategories' : '' ?>?"
                      <?= $c['in_use'] > 0 ? 'disabled title="Listings still use it"' : '' ?>>Delete</button>
            </form>
          </td>
        </tr>

        <?php if ($isOpen): ?>
          <tr class="cat-panel-row">
            <td colspan="6">
              <div class="cat-panel">
                <h4>Editing <?= e($c['label']) ?></h4>
                <form method="post" class="cat-edit"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                  <input type="hidden" name="open" value="<?= e($c['id']) ?>">
                  <div><label>Label</label><input type="text" name="label" value="<?= e($c['label']) ?>" required maxlength="120"></div>
                  <div><label>Icon</label><input type="text" name="icon" value="<?= e($c['icon']) ?>" maxlength="16"></div>
                  <div><label>ID</label><input type="text" value="<?= e($c['id']) ?>" disabled
                       title="Fixed: listings and every category URL are built from it"></div>
                  <div class="cat-edit-go"><button class="btn btn-sm btn-primary">Save category</button></div>
                </form>
                <p class="form-note" style="margin:2px 0 16px">The ID cannot change — listings store it and
                  every category address is built from it. The label and icon are what people see.</p>

                <?php if (category_types_ready()): ?>
                  <h4>Subcategories <span class="mute" style="font-weight:400">(<?= count($mine) ?>)</span></h4>
                  <?php if (!$mine): ?>
                    <p class="mute" style="margin:0 0 12px">None yet. Add one below.</p>
                  <?php else: ?>
                    <div class="table-wrap table-narrow">
                      <table class="table">
                        <tr><th>Name</th><th>Schema.org type</th><th>Order</th><th>Listings</th><th></th></tr>
                        <?php foreach ($mine as $t): ?>
                          <?php $rowOpen = $editType === (int)$t['id']; ?>
                          <tr>
                            <td><strong><?= e($t['label']) ?></strong></td>
                            <td>
                              <?php if ($t['schema_type'] !== ''): ?>
                                <code class="ct-type"><?= e($t['schema_type']) ?></code>
                              <?php else: ?>
                                <span class="mute">none — plain LocalBusiness</span>
                              <?php endif; ?>
                            </td>
                            <td class="mute"><?= (int)$t['sort'] ?></td>
                            <td><?= (int)$t['in_use'] ?></td>
                            <td class="cat-acts">
                              <a class="btn btn-sm btn-ghost"
                                 href="/superadmin/categories?edit=<?= e(urlencode($c['id'])) ?>&type=<?= $rowOpen ? 0 : (int)$t['id'] ?>#cat-<?= e($c['id']) ?>"><?= $rowOpen ? 'Close' : 'Edit' ?></a>
                              <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="action" value="type_delete">
                                <input type="hidden" name="type_id" value="<?= (int)$t['id'] ?>">
                                <input type="hidden" name="open" value="<?= e($c['id']) ?>">
                                <button class="btn btn-sm btn-danger" data-confirm="Remove &quot;<?= e($t['label']) ?>&quot;?">Delete</button>
                              </form>
                            </td>
                          </tr>
                          <?php if ($rowOpen): ?>
                            <tr><td colspan="5" class="ct-editrow">
                              <?php require __DIR__ . '/_type-form.php'; ?>
                            </td></tr>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </table>
                    </div>
                  <?php endif; ?>

                  <?php // The add form is the edit form with nothing in it. ?>
                  <?php $t = null; require __DIR__ . '/_type-form.php'; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card card-pad">
    <h3>Add category</h3>
    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <label>Label *</label><input type="text" name="label" required maxlength="120" placeholder="e.g. Landscaping">
      <label>Icon (emoji)</label><input type="text" name="icon" maxlength="8" placeholder="🌿">
      <label>ID (optional — defaults to slug of label)</label><input type="text" name="id" maxlength="40" placeholder="landscaping">
      <button class="btn btn-primary" style="margin-top:14px">Add category</button>
    </form>

    <?php if (category_types_ready()): ?>
      <hr style="margin:22px 0;border:0;border-top:1px solid var(--border)">
      <h3>Subcategories</h3>
      <p class="mute ed-note">Open a category on the left to see and edit the trades under it.
        Each one can carry a <strong>Schema.org type</strong>, which is what tells Google the
        business is a plumber rather than a business. The list of types is fixed — they are
        names Schema.org defines, and one it does not know is markup a search engine throws
        away. When none fits, leave it blank: the listing stays a plain LocalBusiness, which
        is true of every listing here.</p>
      <form method="post" style="margin-top:10px"><?= csrf_field() ?>
        <input type="hidden" name="action" value="type_seed">
        <button class="btn btn-sm btn-ghost"
                data-confirm="Add back any of the built-in subcategories that are missing? Nothing you have added or renamed is touched.">Restore the built-in list</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
