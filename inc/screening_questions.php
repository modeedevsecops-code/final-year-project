<?php
// Health questionnaire fields shared by the member self-check (screening.php)
// and the officer screening (screen_donor.php). Expects $sf: the values to
// pre-fill (sex, genotype, answers[key] = yes/no).
$sf = $sf ?? [];
$sfA = $sf['answers'] ?? [];
$sfSex = $sf['sex'] ?? '';
$sfGeno = $sf['genotype'] ?? '';
?>
<div class="bl-fields" style="margin-bottom:1rem;">
  <div class="bl-field">
    <label for="sf-sex">Sex</label>
    <select id="sf-sex" name="sex" required>
      <option value="">Select…</option>
      <option value="female" <?= $sfSex === 'female' ? 'selected' : '' ?>>Female</option>
      <option value="male" <?= $sfSex === 'male' ? 'selected' : '' ?>>Male</option>
    </select>
  </div>
  <div class="bl-field">
    <label for="sf-genotype">Genotype <small>(if known)</small></label>
    <select id="sf-genotype" name="genotype">
      <?php foreach (['unknown' => "Don't know", 'AA' => 'AA', 'AS' => 'AS', 'AC' => 'AC', 'SS' => 'SS', 'SC' => 'SC'] as $gv => $gl): ?>
        <option value="<?= $gv ?>" <?= ($sfGeno ?: 'unknown') === $gv ? 'selected' : '' ?>><?= $gl ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="bl-qs">
<?php foreach (bl_screening_questions() as $k => [$q, $days, $reason, $deferOn, $femaleOnly]):
    $cur = $sfA[$k] ?? ''; ?>
  <div class="bl-q"<?= $femaleOnly ? ' data-female-only' : '' ?>>
    <div class="bl-q-text"><?= htmlspecialchars($q) ?><?= $femaleOnly ? '<small>Women only</small>' : '' ?></div>
    <div class="bl-seg" role="radiogroup" aria-label="<?= htmlspecialchars($q) ?>">
      <?php foreach (['yes' => 'Yes', 'no' => 'No'] as $val => $lbl): $id = 'q-' . $k . '-' . $val; ?>
        <input type="radio" id="<?= $id ?>" name="answers[<?= $k ?>]" value="<?= $val ?>" <?= $cur === $val ? 'checked' : '' ?>
               class="<?= $val === $deferOn ? 'is-flag' : '' ?>" <?= $femaleOnly ? '' : 'required' ?>>
        <label for="<?= $id ?>"><?= $lbl ?></label>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<script>
// The pregnancy question only applies to women.
(function () {
  var sex = document.getElementById('sf-sex'); if (!sex) return;
  function sync() {
    document.querySelectorAll('[data-female-only]').forEach(function (row) {
      var on = sex.value === 'female';
      row.style.display = on ? '' : 'none';
      row.querySelectorAll('input').forEach(function (i) { i.required = on; if (!on) i.checked = false; });
    });
  }
  sex.addEventListener('change', sync); sync();
})();
</script>
