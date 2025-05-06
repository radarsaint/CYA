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
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Create Your Character</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    /* Wizard styles */
    .wizard { max-width:600px; margin:2rem auto; }
    .progress { display:flex; margin-bottom:1rem; }
    .progress div { flex:1; height:8px; background:#ccc; margin-right:4px; }
    .progress div.active { background:#1976d2; }
    .step { display:none; }
    .step.active { display:block; }
    .buttons { display:flex; justify-content:space-between; margin-top:1.5rem; }
    button { padding:.6rem 1.2rem; background:#1976d2; color:#fff; border:none; border-radius:4px; }
    button[disabled] { background:#999; cursor:not-allowed; }
    select,input[type="text"] { width:100%; padding:.5rem; margin-top:.3rem;
        border:1px solid #ccc; border-radius:4px; }
    label { display:block; margin-bottom:1rem; }
    .point-buy label { display:flex; align-items:center; margin-bottom:.5rem; }
    .point-buy button { width:2rem; margin:0 .5rem; }
    .point-buy span { width:2rem; text-align:center; display:inline-block; }
  </style>
</head>
<body>
  <div class="wrapper wizard">
    <div class="progress" id="progress">
      <!-- ten bars -->
      <div class="active"></div><div></div><div></div><div></div><div></div>
      <div></div><div></div><div></div><div></div><div></div>
    </div>

    <form id="charForm" action="process_character.php" method="post" novalidate>
      <!-- 1: Name -->
      <div class="step active">
        <h2>Step 1: Name Your Legend</h2>
        <label>Character Name
          <input type="text" name="name" required maxlength="50">
        </label>
      </div>

      <!-- 2: Race -->
      <div class="step">
        <h2>Step 2: Choose Your Kind</h2>
        <label>Race
          <select name="race" id="raceSelect" required>
            <option value="">– select race –</option>
          </select>
        </label>
      </div>

      <!-- 3: Wellspring -->
      <div class="step">
        <h2>Step 3: Your Wellspring</h2>
        <div id="wellspringContainer"></div>
      </div>

      <!-- 4: Focus Type -->
      <div class="step">
        <h2>Step 4: Body or Mind?</h2>
        <div id="focusTypeContainer"></div>
      </div>

      <!-- 5: Specific Focus -->
      <div class="step">
        <h2>Step 5: Refine Your Focus</h2>
        <label>Focus
          <select name="focus" id="focusSelect" required>
            <option value="">– select focus –</option>
          </select>
        </label>
      </div>

      <!-- 6: Dispositions -->
      <div class="step">
        <h2>Step 6: Tactical Flavor</h2>
        <label>Social Disposition
          <select name="social_disposition" id="socialDisp" required></select>
        </label>
        <label>Battle Disposition
          <select name="battle_disposition" id="battleDisp" required></select>
        </label>
      </div>

      <!-- 7: Awakening Story -->
      <div class="step">
        <h2>Step 7: Your Awakening</h2>
        <label>Awakening Story
          <select name="awakening_story" id="awakeningSelect" required>
            <option value="">– select story –</option>
          </select>
        </label>
      </div>

      <!-- 8: Point‑Buy -->
      <div class="step">
        <h2>Step 8: Distribute Your Gifts</h2>
        <div class="point-buy">
          <p>You have <span id="ptsRemaining"></span> points to assign.</p>
          <label>Luck 
            <button type="button" data-stat="luck" data-action="dec">–</button>
            <span id="luckVal">1</span>
            <button type="button" data-stat="luck" data-action="inc">+</button>
          </label>
          <label>Speed 
            <button type="button" data-stat="speed" data-action="dec">–</button>
            <span id="speedVal">1</span>
            <button type="button" data-stat="speed" data-action="inc">+</button>
          </label>
          <label>Endurance 
            <button type="button" data-stat="endurance" data-action="dec">–</button>
            <span id="enduranceVal">1</span>
            <button type="button" data-stat="endurance" data-action="inc">+</button>
          </label>
        </div>
        <input type="hidden" name="luck" id="inputLuck" value="1">
        <input type="hidden" name="speed" id="inputSpeed" value="1">
        <input type="hidden" name="endurance" id="inputEndurance" value="1">
      </div>

      <!-- 9: Saga & Mask -->
      <div class="step">
        <h2>Step 9: Forge Your Saga</h2>
        <label>Saga
          <select name="saga" id="sagaSelect" required>
            <option value="">– select saga –</option>
          </select>
        </label>
        <label>Mask
          <select name="mask" id="maskSelect" required>
            <option value="">– select mask –</option>
          </select>
        </label>
      </div>

      <!-- 10: Review -->
      <div class="step">
        <h2>Final Step: Review & Forge</h2>
        <p>Click “Forge My Character” to complete your creation.</p>
      </div>

      <div class="buttons">
        <button type="button" id="prevBtn" disabled>← Back</button>
        <button type="button" id="nextBtn">Next →</button>
      </div>
    </form>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', async () => {
    // 1) The EXACT list of config types:
    const types = [
      'races',
      'wellsprings',
      'focusTypes',
      'socialdisposition',
      'battledisposition',
      'awakeningstory',
      'saga',
      'masks',
      'essentia',
      'corporea',
      'mainstats'
    ];

    // 2) Fetch & dispatch each one
    for (let type of types) {
      try {
        const res  = await fetch(`api/config.php?type=${type}`);
        const data = await res.json();
        handleConfig(type, data);
      } catch(err) {
        console.error("Failed to load", type, err);
      }
    }

// 3) Dispatcher
function handleConfig(type, data) {
  switch (type) {
    case 'races': {
      const sel = document.getElementById('raceSelect');
      Object.entries(data).forEach(([group, val]) => {
        const og = document.createElement('optgroup');
        og.label = group.replace(/([a-z])([A-Z])/g, '$1 $2');
        const arr = Array.isArray(val) ? val : [].concat(...Object.values(val));
        arr.forEach(item => {
          const tag = (typeof item === 'string' ? item : item.tag);
          const o   = document.createElement('option');
          o.value       = tag;
          o.textContent = tag;
          og.appendChild(o);
        });
        sel.appendChild(og);
      });
      break;
    }

    case 'wellsprings': {
      const ct = document.getElementById('wellspringContainer');
      data.forEach(w => {
        const lbl = document.createElement('label');
        lbl.innerHTML = `
          <input 
            type="radio" 
            name="wellspring" 
            value="${w.id}" 
            required
          > ${w.label}
        `;
        ct.appendChild(lbl);
        ct.appendChild(document.createElement('br'));
      });
      break;
    }

    case 'focusTypes': {
      const ct = document.getElementById('focusTypeContainer');
      data.forEach(ft => {
        const lbl = document.createElement('label');
        lbl.innerHTML = `
          <input 
            type="radio" 
            name="focus_type" 
            value="${ft}" 
            required
          > ${ft}
        `;
        ct.appendChild(lbl);
      });
      break;
    }

    case 'essentia':
      window.essentiaFoci = data;
      break;

    case 'corporea':
      window.corporeaFoci = data;
      break;

    case 'socialdisposition':
    case 'battledisposition': {
      const selId   = type === 'socialdisposition' ? 'socialDisp' : 'battleDisp';
      const dispSel = document.getElementById(selId);

      // reset and then unwrap either data or data.dispositions
      dispSel.innerHTML = '<option value="">– select –</option>';
      const list = Array.isArray(data)
        ? data
        : (Array.isArray(data.dispositions) ? data.dispositions : []);

      list.forEach(d => {
        const o = document.createElement('option');
        if (typeof d === 'string') {
          o.value       = d;
          o.textContent = d;
        } else {
          o.value       = d.id ?? '';
          o.textContent = d.label ?? d.id ?? '';
        }
        dispSel.appendChild(o);
      });
      break;
    }

    case 'awakeningstory': {
      const sel = document.getElementById('awakeningSelect');
      data.forEach(a => {
        const o = document.createElement('option');
        o.value       = a.id;
        o.textContent = a.label;
        sel.appendChild(o);
      });
      break;
    }

    case 'saga': {
      const sel = document.getElementById('sagaSelect');
      data.forEach(s => {
        const o = document.createElement('option');
        if (typeof s === 'string') {
          o.value       = s;
          o.textContent = s;
        } else {
          o.value       = s.id ?? '';
          o.textContent = s.label ?? s.id ?? '';
        }
        sel.appendChild(o);
      });
      break;
    }

    case 'masks': {
      const sel = document.getElementById('maskSelect');
      data.forEach(m => {
        const o = document.createElement('option');
        if (typeof m === 'string') {
          o.value       = m;
          o.textContent = m;
        } else {
          o.value       = m.id ?? '';
          o.textContent = m.label ?? m.id ?? '';
        }
        sel.appendChild(o);
      });
      break;
    }

    case 'mainstats':
      window.pointBuyConfig = data;
      break;

    default:
      console.warn(`No handler for config type: ${type}`);
  }
}

    // 4) Wire focus → specific focus
    document.body.addEventListener('change', e => {
      if (e.target.name==='focus_type') {
        const sel = document.getElementById('focusSelect');
        sel.innerHTML = `<option value="">– select focus –</option>`;
        const list = e.target.value==='Essentia'
          ? window.essentiaFoci
          : window.corporeaFoci;
        list.forEach(f => {
          const o = document.createElement('option');
          o.value = f; o.textContent = f;
          sel.appendChild(o);
        });
      }
    });

    // 5) Point‑buy and wizard navigation
    const max=4, stats={luck:1,speed:1,endurance:1};
    const rem = ()=> max - (stats.luck + stats.speed + stats.endurance);
    function upd() {
      document.getElementById('ptsRemaining').textContent = rem();
      for (let k in stats) {
        document.getElementById(k+'Val').textContent = stats[k];
        document.getElementById('input'+k.charAt(0).toUpperCase()+k.slice(1))
          .value = stats[k];
      }
      document.querySelectorAll('.point-buy button').forEach(b=>{
        b.disabled = (b.dataset.action==='inc'? rem()===0 : stats[b.dataset.stat]===1);
      });
    }
    document.querySelectorAll('.point-buy button').forEach(b =>
      b.addEventListener('click', ()=>{
        const {stat,action} = b.dataset;
        if (action==='inc' && rem()>0) stats[stat]++;
        if (action==='dec' && stats[stat]>1) stats[stat]--;
        upd();
      })
    );
    upd();

    // 6) Wizard next/back
    const steps = [...document.querySelectorAll('.step')],
          bars  = [...document.querySelectorAll('.progress div')],
          prev  = document.getElementById('prevBtn'),
          next  = document.getElementById('nextBtn'),
          form  = document.getElementById('charForm');
    let idx = 0;
    function show(i) {
      steps.forEach((s,j)=> s.classList.toggle('active', j===i));
      bars.forEach((b,j)=> b.classList.toggle('active', j<=i));
      prev.disabled = (i===0);
      next.textContent = (i===steps.length-1 ? 'Forge My Character' : 'Next →');
    }
    next.addEventListener('click', ()=>{
      const inputs = [...steps[idx].querySelectorAll('input,select')];
      for (let el of inputs) {
        if (!el.checkValidity()) { el.reportValidity(); return; }
      }
      if (idx === steps.length-1) {
        form.submit();
      } else {
        idx++;
        show(idx);
      }
    });
    prev.addEventListener('click', ()=>{
      if (idx>0) { idx--; show(idx); }
    });
    show(0);
  });
  </script>
</body>
</html>
