<?php
// character.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
session_start();

// 1) Redirect if not logged in
if (empty($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}

// 2) Prevent re-creation
$stmt = $pdo->prepare("SELECT COUNT(*) FROM characters WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() > 0) {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Create Your Character</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    /* ───── Wizard base styles ───── */
    .wizard    { max-width: 600px; margin: 2rem auto; }
    .progress  { display: flex; margin-bottom: 1rem; }
    .progress div {
      flex:1; height:8px; background:#ccc; margin-right:4px;
      transition:background .3s;
    }
    .progress div.active { background:#1976d2; }
    .step      { display:none; }
    .step.active { display:block; }
    .buttons   { display:flex; justify-content:space-between; margin-top:1.5rem; }
    button     { padding:.6rem 1.2rem; background:#1976d2; color:#fff; border:none; border-radius:4px; }
    button[disabled] { background:#999; cursor:not-allowed; }
    select, input[type="text"] { width:100%; padding:.5rem; margin-top:.3rem; border:1px solid #ccc; border-radius:4px; }
    label      { display:block; margin-bottom:1rem; }
    /* point‑buy */
    .point-buy label { display:flex; align-items:center; margin-bottom:.5rem; }
    .point-buy button { width:2rem; margin: 0 .5rem; }
    .point-buy span { width:2rem; text-align:center; display:inline-block; }
    .point-buy p { margin-bottom:1rem; }
  </style>
</head>
<body>
  <div class="wrapper wizard">
    <div class="progress" id="progress">
      <div class="active"></div>
      <div></div><div></div><div></div><div></div>
      <div></div><div></div><div></div><div></div>
      <div></div>
    </div>

    <form id="charForm" action="process_character.php" method="post" novalidate>
      <!-- Step 1: Name -->
      <div class="step active">
        <h2>Step 1: Name Your Legend</h2>
        <label>
          Character Name
          <input type="text" name="name" required maxlength="50">
        </label>
      </div>

      <!-- Step 2: Race -->
      <div class="step">
        <h2>Step 2: Choose Your Kind</h2>
        <label>
          Race
          <select name="race" id="raceSelect" required>
            <option value="">– select race –</option>
          </select>
        </label>
      </div>

      <!-- Step 3: Wellspring -->
      <div class="step">
        <h2>Step 3: Your Wellspring</h2>
        <div id="wellspringContainer"></div>
      </div>

      <!-- Step 4: Focus Type -->
      <div class="step">
        <h2>Step 4: Body or Mind?</h2>
        <div id="focusTypeContainer"></div>
      </div>

      <!-- Step 5: Specific Focus -->
      <div class="step">
        <h2>Step 5: Refine Your Focus</h2>
        <label>
          Focus
          <select name="focus" id="focusSelect" required>
            <option value="">– select focus –</option>
          </select>
        </label>
      </div>

      <!-- Step 6: Dispositions -->
      <div class="step">
        <h2>Step 6: Tactical Flavor</h2>
        <label>
          Social Disposition
          <select name="social_disposition" id="socialDisp" required></select>
        </label>
        <label>
          Battle Disposition
          <select name="battle_disposition" id="battleDisp" required></select>
        </label>
      </div>

      <!-- Step 7: Awakening Story -->
      <div class="step">
        <h2>Step 7: Your Awakening</h2>
        <label>
          Awakening Story
          <select name="awakening_story" id="awakeningSelect" required>
            <option value="">– select story –</option>
          </select>
        </label>
      </div>

      <!-- Step 8: Distribute Your Gifts -->
      <div class="step">
        <h2>Step 8: Distribute Your Gifts</h2>
        <div class="point-buy">
          <p>You have <span id="ptsRemaining"></span> points to assign.</p>
          <label>
            Luck
            <button type="button" data-stat="luck" data-action="dec">–</button>
            <span id="luckVal">1</span>
            <button type="button" data-stat="luck" data-action="inc">+</button>
          </label>
          <label>
            Speed
            <button type="button" data-stat="speed" data-action="dec">–</button>
            <span id="speedVal">1</span>
            <button type="button" data-stat="speed" data-action="inc">+</button>
          </label>
          <label>
            Endurance
            <button type="button" data-stat="endurance" data-action="dec">–</button>
            <span id="enduranceVal">1</span>
            <button type="button" data-stat="endurance" data-action="inc">+</button>
          </label>
        </div>
        <input type="hidden" name="luck"      id="inputLuck"      value="1">
        <input type="hidden" name="speed"     id="inputSpeed"     value="1">
        <input type="hidden" name="endurance" id="inputEndurance" value="1">
      </div>

      <!-- Step 9: Forge Your Saga -->
      <div class="step">
        <h2>Step 9: Forge Your Saga</h2>
        <label>
          Saga
          <select name="saga" id="sagaSelect">
            <option value="">– select saga –</option>
          </select>
        </label>
        <label>
          Core Principle
          <select name="core_principle" id="corePrincipleSelect">
            <option value="">– select principle –</option>
          </select>
        </label>
        <label>
          Mask
          <select name="mask" id="maskSelect">
            <option value="">– select mask –</option>
          </select>
        </label>
      </div>

      <!-- Step 10: Review & Submit -->
      <div class="step">
        <h2>Final Step: Review & Forge</h2>
        <p>Click “Forge My Character” to complete your creation.</p>
      </div>

      <!-- nav buttons -->
      <div class="buttons">
        <button type="button" id="prevBtn" disabled>← Back</button>
        <button type="button" id="nextBtn">Next →</button>
      </div>
    </form>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // 1) List all JSON “types” (maps to data/*.json)
    const types = [
      'races',
      'wellsprings',
      'socialdisposition',
      'battledisposition',
      'awakeningstory',
      'saga',
      'coreprincipal',
      'masks',
      'essentia',
      'corporea',
      'mainstats'
    ];

    types.forEach(type => {
      fetch(`api/config.php?type=${type}`)
        .then(res => res.json())
        .then(data => {
          switch (type) {
            case 'races':
              const raceSel = document.getElementById('raceSelect');
              Object.entries(data).forEach(([group, list]) => {
                const og = document.createElement('optgroup');
                og.label = group;
                list.forEach(item => {
                  const o = document.createElement('option');
                  o.value = item; o.textContent = item;
                  og.appendChild(o);
                });
                raceSel.appendChild(og);
              });
              break;

            case 'wellsprings':
              const wsCt = document.getElementById('wellspringContainer');
              data.forEach(w => {
                const lbl = document.createElement('label');
                lbl.innerHTML = `<input type="radio" name="wellspring" value="${w}" required> ${w}`;
                wsCt.appendChild(lbl);
                wsCt.appendChild(document.createElement('br'));
              });
              break;

            case 'socialdisposition':
            case 'battledisposition':
              const selId = type === 'socialdisposition' ? 'socialDisp' : 'battleDisp';
              const dispSel = document.getElementById(selId);
              data.forEach(d => {
                const o = document.createElement('option');
                o.value = d; o.textContent = d;
                dispSel.appendChild(o);
              });
              break;

            case 'awakeningstory':
              const aw = document.getElementById('awakeningSelect');
              data.forEach(s => {
                const o = document.createElement('option');
                o.value = s; o.textContent = s;
                aw.appendChild(o);
              });
              break;

            case 'saga':
              const saga = document.getElementById('sagaSelect');
              data.forEach(s => {
                const o = document.createElement('option');
                o.value = s; o.textContent = s;
                saga.appendChild(o);
              });
              break;

            case 'coreprincipal':
              const cp = document.getElementById('corePrincipleSelect');
              data.forEach(p => {
                const o = document.createElement('option');
                o.value = p; o.textContent = p;
                cp.appendChild(o);
              });
              break;

            case 'masks':
              const mkSel = document.getElementById('maskSelect');
              data.forEach(mk => {
                const o = document.createElement('option');
                o.value = mk; o.textContent = mk;
                mkSel.appendChild(o);
              });
              break;

            case 'essentia':
              window.essentiaFoci = data;
              break;

            case 'corporea':
              window.corporeaFoci = data;
              break;

            case 'mainstats':
              window.pointBuyConfig = data;
              break;
          }
        });
    });

    // 2) Wire focus_type → focusSelect
    document.getElementsByName('focus_type').forEach(radio => {
      radio.addEventListener('change', e => {
        const focusSel = document.getElementById('focusSelect');
        focusSel.innerHTML = `<option value="">– select focus –</option>`;
        const list = e.target.value === 'Essentia'
          ? window.essentiaFoci
          : window.corporeaFoci;
        list.forEach(f => {
          const o = document.createElement('option');
          o.value = f; o.textContent = f;
          focusSel.appendChild(o);
        });
      });
    });

    // 3) Your existing point‑buy + wizard nav code:
    const max = 4, stats = { luck:1, speed:1, endurance:1 };
    const rem = () => max - (stats.luck+stats.speed+stats.endurance);
    function upd() {
      document.getElementById('ptsRemaining').textContent = rem();
      for (let s in stats) {
        document.getElementById(s+'Val').textContent = stats[s];
        document.getElementById('input'+s.charAt(0).toUpperCase()+s.slice(1)).value = stats[s];
      }
      document.querySelectorAll('.point-buy button').forEach(b => {
        let st=b.dataset.stat, ac=b.dataset.action;
        b.disabled = ac==='inc' ? rem()===0 : stats[st]===1;
      });
    }
    document.querySelectorAll('.point-buy button').forEach(b=>{
      b.addEventListener('click',()=>{
        let {stat,action}=b.dataset;
        if (action==='inc'&&rem()>0) stats[stat]++;
        if (action==='dec'&&stats[stat]>1) stats[stat]--;
        upd();
      });
    });
    upd();

    const steps = document.querySelectorAll('.step'),
          bars  = document.querySelectorAll('.progress div'),
          prev  = document.getElementById('prevBtn'),
          next  = document.getElementById('nextBtn'),
          form  = document.getElementById('charForm');
    let current = 0;
    function show(idx) {
      steps.forEach((s,i)=>s.classList.toggle('active',i===idx));
      bars.forEach((b,i)=>b.classList.toggle('active',i<=idx));
      prev.disabled = idx===0;
      next.textContent = idx===steps.length-1 ? 'Forge My Character' : 'Next →';
    }
    next.addEventListener('click',()=>{
      const inputs = steps[current].querySelectorAll('input,select');
      for (let el of inputs) {
        if (!el.checkValidity()) { el.reportValidity(); return; }
      }
      if (current===steps.length-1) return form.submit();
      current++; show(current);
    });
    prev.addEventListener('click',()=>{
      if (current>0) { current--; show(current); }
    });

    // Initial render
    show(0);
  });
  </script>
</body>
</html>
