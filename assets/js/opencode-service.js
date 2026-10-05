/* ═══════════════════════════════════════════════════════════════
   assets/js/opencode-service.js
   OpenCode Zen AI lane — generation + AI chat editing with fallback.

   CHAIN (never empty):
     1. OpenCode Zen (all free models, server-side key, FlowCraft-fed)
     2. Gemini via api/generate.php?action=refine (server free key)
     3. Server templates (flowcraft_generate — premium, guaranteed)

   Customer requirements are ANALYSED first (design brief), then the
   brief + FlowCraft layout direction feed every generation call so
   output looks premium even when fields are "(unspecified)".
   Tanglish / Tamil / English all understood; code stays English.
   ═══════════════════════════════════════════════════════════════ */
window.OpenCodeAI = (function () {
  'use strict';

  const KEY_MODEL = 'webcraft_opencode_model';

  const FREE_MODELS = [
    { id: 'space-bunny-free',                label: 'Space Bunny (Free · working)', free: true },
    { id: 'big-pickle',                      label: 'Big Pickle (Free stealth)', free: true },
    { id: 'muse-spark-1.3-contributor-free', label: 'Muse Spark 1.3 (Free)', free: true },
    { id: 'muse-spark-1.2-contributor-free', label: 'Muse Spark 1.2 (Free)', free: true },
    { id: 'mimo-v2.5-free',                  label: 'MiMo V2.5 (Free)', free: true },
    { id: 'mimo-v2.6-flash-free',            label: 'MiMo V2.6 Flash (Free)', free: true },
    { id: 'nemotron-3.5-lightning-free',     label: 'Nemotron 3.5 Lightning (Free)', free: true },
    { id: 'jev-1.13-free',                   label: 'Jev 1.13 (Free)', free: true },
    { id: 'longcat-2.5-preview-free',        label: 'LongCat 2.5 Preview (Free)', free: true }
  ];

  const VARIATIONS = ['classic', 'bold', 'editorial'];
  const VAR_META = {
    classic:   { name: 'Concept 1 — Modern Minimal & Crisp',       badge: 'Clean & Professional',  description: 'Light balanced aesthetic, glassmorphism sticky nav, refined cards. (OpenCode AI)' },
    bold:      { name: 'Concept 2 — Bold Dynamic & Bento Grid',    badge: 'High-Impact & Modern',  description: 'Oversized type, gradient mesh, bento cards, high-conversion flow. (OpenCode AI)' },
    editorial: { name: 'Concept 3 — Executive Luxury & Dark Mode', badge: 'Sleek Dark Glass',      description: 'Obsidian backdrop, aurora glows, frosted glass, luxury type. (OpenCode AI)' }
  };

  function base() {
    try {
      if (typeof SITE_URL !== 'undefined' && SITE_URL) return SITE_URL;
    } catch (e) {}
    return '';
  }
  function endpoint(path) {
    const b = base();
    return (b ? b : '') + path;
  }

  async function post(path, body, timeoutMs) {
    timeoutMs = timeoutMs || 95000;
    const ctrl = (typeof AbortController !== 'undefined') ? new AbortController() : null;
    const timer = ctrl ? setTimeout(() => { try { ctrl.abort(); } catch (e) {} }, timeoutMs) : null;
    try {
      const resp = await fetch(endpoint(path), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
        signal: ctrl ? ctrl.signal : undefined
      });
      if (!resp.ok) throw new Error('HTTP ' + resp.status + ' on ' + path);
      return resp.json();
    } catch (e) {
      if (e && e.name === 'AbortError') throw new Error('AI request timed out — templates will take over');
      throw e;
    } finally {
      if (timer) clearTimeout(timer);
    }
  }

  function getModel() {
    try {
      return localStorage.getItem(KEY_MODEL) || FREE_MODELS[0].id;
    } catch (e) { return FREE_MODELS[0].id; }
  }
  function setModel(m) {
    const found = FREE_MODELS.some(x => x.id === m);
    if (!found) return;
    try { localStorage.setItem(KEY_MODEL, m); } catch (e) {}
    service.selectedModel = m;
    try {
      ['ai-model-select', 'wiz-model-select'].forEach(id => {
        const sel = document.getElementById(id);
        if (sel) sel.value = m;
      });
      const lbl = document.getElementById('ai-model-label');
      if (lbl) lbl.textContent = (FREE_MODELS.find(x => x.id === m) || {}).label || m;
    } catch (e) {}
  }

  /* ── Tanglish hint enrichment (mirrors builder.php) ── */
  const TANGLISH_HINTS = [
    [/\bmaathu|maathunga\b/i, 'change'],
    [/\bperiya|periyathu\b/i, 'make it bigger'],
    [/\bchinna|sinnathu\b/i, 'make it smaller'],
    [/\baakku|aakunga\b/i, 'make it'],
    [/\badd pannu|add pannunga\b/i, 'add'],
    [/\bremove pannu\b/i, 'remove'],
    [/\bkaattu|kaattunga\b/i, 'show'],
    [/\bazhaga|azhagana\b/i, 'beautiful'],
    [/\bkizha|keezha\b/i, 'at the bottom'],
    [/\bmela|mele\b/i, 'at the top']
  ];
  function enrichTanglish(text) {
    let out = String(text || '');
    TANGLISH_HINTS.forEach(([rx, en]) => { if (rx.test(out)) out += ` [hint: ${en}]`; });
    return out;
  }

  /* ── Lane primitives ── */
  async function getModels() {
    return post('/api/opencode.php', { action: 'models' });
  }

  async function analyzeRequirements(data, onProgress) {
    if (onProgress) onProgress({ stage: 'analyzing', pct: 12, message: 'OpenCode AI analysing requirements…' });
    try {
      const j = await post('/api/opencode.php', { action: 'analyze', data, model: getModel() }, 12000);
      if (j && j.brief) {
        if (onProgress) onProgress({ stage: 'analyzing', pct: 30, message: 'Design brief ready' + (j.model ? ' (' + j.model + ')' : '') });
        return j.brief;
      }
    } catch (e) { console.warn('[OpenCodeAI] analyze failed, template brief:', e?.message); }
    const d = data || {};
    return `AUDIENCE: ${d.biz_audience || 'Modern clients'}\nSECTIONS: ${(d.sections || []).join(', ') || 'hero, services, about, metrics, contact, footer'}\nPALETTE: ${d.color_palette || 'purple'}\nTYPE: Plus Jakarta Sans + Inter\nDIFFERENTIATORS: glass nav; clay CTAs; scroll-reveal`;
  }

  /* Original flow: all 3 slots on gemini-3.5-flash-lite (~18s each, parallel).
     Diversity via temperature + layout brief + slot direction only. */
  const SLOT_LANES = [
    [ { kind: 'fast',  model: 'gemini-3.5-flash-lite', temp: 0.7 },
      { kind: 'fast',  model: 'gemini-3.5-flash',      temp: 0.8 } ],
    [ { kind: 'fast',  model: 'gemini-3.5-flash-lite', temp: 0.95 },
      { kind: 'fast',  model: 'gemini-3.5-flash',      temp: 0.9 } ],
    [ { kind: 'fast',  model: 'gemini-3.5-flash-lite', temp: 0.55 },
      { kind: 'fast',  model: 'gemini-3.5-flash',      temp: 0.6 } ]
  ];

  /* One fast-lane call (Gemini single-shot full page ~20s). */
  async function generateFast(data, variation, mode, brief, slot, model, temp) {
    const j = await post('/api/opencode.php', {
      action: 'fast_one', data, variation, mode, brief,
      model: model || undefined, temperature: (typeof temp === 'number' ? temp : undefined),
      slot: (typeof slot === 'number' ? slot : -1)
    }, 31000);
    if (j && j.success && j.html && j.html.length > 500) return j;
    throw new Error(((j && (j.errors || [])[0]) || (j && j.error) || 'fast lane failed') + ' [' + variation + ']');
  }

  /* OpenCode QUICK lane (space-bunny single-shot ~40s — 3rd AI leg). */
  async function generateQuick(data, variation, mode, brief, slot, temp) {
    const j = await post('/api/opencode.php', {
      action: 'quick_one', data, variation, mode, brief, model: getModel(),
      temperature: (typeof temp === 'number' ? temp : undefined),
      slot: (typeof slot === 'number' ? slot : -1)
    }, 100000);
    if (j && j.success && j.html && j.html.length > 500) return j;
    throw new Error(((j && (j.errors || [])[0]) || (j && j.error) || 'quick lane failed') + ' [' + variation + ']');
  }

  /* Run one slot through its lane list in order — first success wins. */
  async function runSlotLanes(data, variation, mode, brief, slot, lanes) {
    let lastErr = null;
    for (const lane of lanes) {
      try {
        if (lane.kind === 'quick') {
          const j = await generateQuick(data, variation, mode, brief, slot, lane.temp);
          return { j, engine: 'opencode-quick' };
        }
        const j = await generateFast(data, variation, mode, brief, slot, lane.model, lane.temp);
        return { j, engine: 'gemini' };
      } catch (e) { lastErr = e; }
    }
    throw lastErr || new Error('slot lanes failed [' + variation + ']');
  }
  /* OpenCode chunked lane (slow but sure) — final net for missing slots.
     Diversity: each missing slot tries a DIFFERENT free model. */
  async function generateOne(data, variation, mode, brief, slot, modelOverride) {
    const j = await post('/api/opencode.php', {
      action: 'generate_one', data, variation, mode, brief, model: modelOverride || getModel(),
      slot: (typeof slot === 'number' ? slot : -1)
    }, 280000);
    if (j && j.success && j.html && j.html.length > 500) return j;
    throw new Error(((j && (j.errors || [])[0]) || (j && j.error) || 'OpenCode generate failed') + ' [' + variation + ']');
  }

  function shapeDesign(v, j, data, mode, brief, engine) {
    return {
      id: v, name: VAR_META[v].name, badge: VAR_META[v].badge + ' · ' + (j.model || 'AI'),
      description: VAR_META[v].description, html: j.html,
      meta: { bizName: data.biz_name, mode, engine, model: j.model, brief, generatedAt: new Date().toISOString() }
    };
  }

  /* ── MAIN: 3 variations, all gemini-3.5-flash-lite (original flow, ~18s each) ──
     Diversity via temperature + layout brief + slot direction.
     Missing slots → instant server templates (caller merges). Bounded ≈ 100s. */
  async function generateConcepts(data, mode, options) {
    options = options || {};
    const onProgress = options.onProgress;
    if (options.model) setModel(options.model);
    const brief = await analyzeRequirements(data, onProgress);
    const t0 = Date.now();
    const secs = () => Math.round((Date.now() - t0) / 1000) + 's';
    if (onProgress) onProgress({
      stage: 'generating', pct: 38,
      message: 'Gemini-lite crafting 3 variations (temps 0.7 / 0.95 / 0.55)…',
      completedCount: 0
    });

    const slots = [null, null, null];
    const errors = [];
    let done = 0, tok = 0;
    const tokStr = () => `· ~${(tok / 1000).toFixed(1)}k tokens`;
    const bump = (msg) => {
      done++;
      if (onProgress) onProgress({
        stage: 'generating', pct: 38 + Math.round((done / VARIATIONS.length) * 57),
        message: msg + ` (${secs()}) ${tokStr()}…`,
        completedCount: done
      });
    };

    // LANE 1 (3 different AIs in parallel, 250ms stagger): first success per slot wins.
    // Bounded 100s — missing slots fall to instant server templates (caller),
    // no slow chunked lane. Total generation ≈ analyze(≤12s) + 100s max.
    let laneDone = false;
    const lane1 = Promise.all(VARIATIONS.map((v, i) =>
      new Promise(r => setTimeout(r, i * 250))
      .then(() => runSlotLanes(data, v, mode, brief, -1, SLOT_LANES[i])).then(
        ({ j, engine }) => {
          if (laneDone) return;
          tok += (j.usage?.prompt || 0) + (j.usage?.completion || 0);
          slots[i] = shapeDesign(v, j, data, mode, brief, engine);
        },
        (e) => { errors.push(v + ': ' + (e?.message || e)); }
      ).then(() => { if (!laneDone && slots[i]) bump(`✓ ${done + 1}/3 variations ready (${slots[i].meta.model || 'AI'})`); })
    ));
    await Promise.race([
      lane1,
      new Promise(r => setTimeout(() => {
        if (!laneDone) errors.push('lane timeout 100s — remaining slots use templates');
        r();
      }, 100000))
    ]);
    laneDone = true;

    const designs = slots.filter(Boolean);
    if (!designs.length) {
      throw new Error('AI lanes down (' + errors.slice(0, 2).join(' | ') + '). Use server templates.');
    }
    if (onProgress) onProgress({ stage: 'done', pct: 100, completedCount: designs.length, message: `✓ ${designs.length}/3 AI variations ready in ${secs()} ${tokStr()}` });
    return { designs, brief, aiCount: designs.length, errors, tokens: tok };
  }

  /* ── 3 AI LAYOUTS inside ONE selected variation ──
     Same style direction, 3 distinct layout slots (A/B/C).
     Throws when the AI lane is down (caller uses template subdesigns). */
  const SLOT_META = [
    { suffix: 'A', label: 'Layout A · Hero Focus' },
    { suffix: 'B', label: 'Layout B · Bento Showcase' },
    { suffix: 'C', label: 'Layout C · Authority Editorial' }
  ];
  async function generateSubDesigns(data, variation, mode, conceptNum, options) {
    options = options || {};
    const onProgress = options.onProgress;
    if (options.model) setModel(options.model);
    const brief = options.brief || await analyzeRequirements(data, onProgress);
    const t0 = Date.now();
    if (onProgress) onProgress({ stage: 'generating', pct: 40, completedCount: 0, message: `3-AI crafting 3 ${variation} layouts (each different)…` });
    const slots = [null, null, null];
    const errors = [];
    let done = 0, tok = 0;
    const tokStr = () => `· ~${(tok / 1000).toFixed(1)}k tokens`;
    // 250ms stagger — 429-safe but fast. Each layout a different primary AI.
    // Bounded 100s — missing layouts fall to template layouts (caller).
    let subDone = false;
    const subAll = Promise.all([0, 1, 2].map((s) =>
      new Promise(r => setTimeout(r, s * 250))
      .then(() => runSlotLanes(data, variation, mode, brief, s, SLOT_LANES[s])).then(
        ({ j, engine }) => {
          tok += (j.usage?.prompt || 0) + (j.usage?.completion || 0);
          slots[s] = {
            id: variation + '-' + SLOT_META[s].suffix,
            name: `${conceptNum}${SLOT_META[s].suffix} · ${SLOT_META[s].label.replace('Layout ', '')} (${variation})`,
            badge: 'AI Layout ' + SLOT_META[s].suffix + ' · ' + (j.model || engine),
            description: `AI-generated ${SLOT_META[s].label} in ${variation} style.`,
            html: j.html,
            meta: { bizName: data.biz_name, mode, engine, model: j.model, brief, generatedAt: new Date().toISOString() }
          };
        },
        (e) => { errors.push('slot' + s + ': ' + (e?.message || e)); }
      ).then(() => {
        if (subDone) return;
        done++;
        if (onProgress) onProgress({ stage: 'generating', pct: 40 + Math.round((done / 3) * 55), completedCount: done, message: `✓ ${done}/3 AI layouts ready (${Math.round((Date.now() - t0) / 1000)}s) ${tokStr()}…` });
      })
    ));
    await Promise.race([
      subAll,
      new Promise(r => setTimeout(() => {
        if (!subDone) errors.push('layout lane timeout 100s — remaining use templates');
        r();
      }, 100000))
    ]);
    subDone = true;
    const designs = slots.filter(Boolean);
    if (!designs.length) throw new Error('AI subdesigns failed (' + errors.slice(0, 2).join(' | ') + '). Using template layouts.');
    if (onProgress) onProgress({ stage: 'done', pct: 100, completedCount: designs.length, message: `✓ ${designs.length}/3 AI layouts ready ${tokStr()}` });
    return { designs, brief, aiCount: designs.length, errors, tokens: tok };
  }

  /* ── Free-model dropdown (live Zen list, recommended first) ──
     OpenCode free models only — no Puter options. */
  function shortLabel(id, known, recommended) {
    const base = known ? known.label.replace(/ \(Free.*$/, '') : id;
    return (id === recommended ? '★ ' : '') + base + ' (Free)';
  }
  async function refreshModelDropdown() {
    let free = null, recommended = null, benchNote = '';
    try {
      const j = await post('/api/opencode.php', { action: 'models' }, 20000);
      if (j && Array.isArray(j.free) && j.free.length) {
        free = j.free;
        recommended = j.recommended || j.free[0];
        const bm = j.benchmark && Array.isArray(j.benchmark.models)
          ? j.benchmark.models.find(m => m.model === recommended) : null;
        if (bm && bm.ok) benchNote = `tested ✓ ${bm.secs}s`;
        else if (bm) benchNote = 'last test failed — auto fallback on';
      }
    } catch (e) { /* static fallback below */ }
    const ids = free || FREE_MODELS.map(m => m.id);
    recommended = ids.includes(recommended) ? recommended : ids[0];
    const cur = getModel();
    ['ai-model-select', 'magic-model-select', 'wiz-model-select'].forEach(selId => {
      const sel = document.getElementById(selId);
      if (!sel) return;
      const keepVal = sel.value || cur;
      sel.innerHTML = '';
      ids.forEach(id => {
        const known = FREE_MODELS.find(m => m.id === id);
        const o = document.createElement('option');
        o.value = id;
        o.textContent = shortLabel(id, known, recommended);
        sel.appendChild(o);
      });
      sel.value = ids.includes(keepVal) ? keepVal : recommended;
    });
    if (!ids.includes(cur)) setModel(recommended);
    else { service.selectedModel = cur; }
    const why = document.getElementById('ai-model-why') || document.getElementById('wiz-model-why') || document.getElementById('magic-model-why');
    if (why) {
      const rec = FREE_MODELS.find(m => m.id === recommended);
      const recName = rec ? rec.label.replace(/ \(Free.*$/, '') : recommended;
      why.textContent = `★ Recommended: ${recName}${benchNote ? ' — ' + benchNote : ''} · tap to change`;
    }
    if (typeof updateModelLabel === 'function') { try { updateModelLabel(); } catch (e) {} }
  }

  /* ── AI CHAT EDIT (fast Gemini first, then OpenCode chunks) ── */
  async function chatAndEdit({ userPrompt, currentHtml = '', bizName = 'Website' }) {
    const instruction = enrichTanglish(userPrompt);
    if (!instruction.trim()) return { conversation: 'Type or say something first.', isEdit: false, updatedHtml: '' };
    try {
      const fj = await post('/api/opencode.php', {
        action: 'fast_edit', current_html: currentHtml, instruction, biz_name: bizName
      }, 35000);
      if (fj && fj.success && fj.html) {
        return {
          conversation: fj.response_msg || '✨ Applied your change.',
          isEdit: true, updatedHtml: fj.html, engine: 'gemini', model: fj.model || 'gemini-flash'
        };
      }
    } catch (fe) { console.warn('[OpenCodeAI] fast edit failed, trying OpenCode:', fe?.message); }
    const j = await post('/api/opencode.php', {
      action: 'edit', current_html: currentHtml, instruction, biz_name: bizName, model: getModel()
    });
    if (j && j.success && j.html) {
      return {
        conversation: j.response_msg || '✨ OpenCode AI applied your change.',
        isEdit: true, updatedHtml: j.html, engine: 'opencode', model: j.model || getModel()
      };
    }
    const err = new Error(((j && (j.errors || [])[0]) || (j && j.error) || 'OpenCode edit failed'));
    err.fallback = (j && j.fallback) || 'gemini-then-smart-engine';
    err.detail = j;
    throw err;
  }

  /* ── SNIPPET EDIT (selected element only — small I/O, fits free caps) ──
     First choice for selected-element micro-edits. Returns snippet HTML. */
  async function editSnippet({ userPrompt, selectedHtml = '', bizName = 'Website' }) {
    const instruction = enrichTanglish(userPrompt);
    if (!instruction.trim() || selectedHtml.trim().length < 20) {
      throw new Error('Select an element on canvas first, then describe the change.');
    }
    const j = await post('/api/opencode.php', {
      action: 'edit_snippet', selected_html: selectedHtml.slice(0, 8000),
      instruction, biz_name: bizName, model: getModel()
    }, 95000);
    if (j && j.success && j.html) {
      return {
        conversation: j.response_msg || '✨ OpenCode AI updated the selected element.',
        isEdit: true, updatedHtml: j.html, engine: 'opencode-snippet', model: j.model || getModel()
      };
    }
    const err = new Error(((j && (j.errors || [])[0]) || (j && j.error) || 'Snippet edit failed'));
    err.detail = j;
    throw err;
  }

  /* ── Combined edit chain for one-click UI use ──
     OpenCode free models → server Gemini/refine → smart template notice.
     (No Puter dependency.) */
  async function editWithFallback({ userPrompt, currentHtml = '', bizName = 'Website' }) {
    const laneErrs = [];
    try {
      return await chatAndEdit({ userPrompt, currentHtml, bizName });
    } catch (e1) {
      laneErrs.push(e1?.message || 'fast+opencode edit failed');
      console.warn('[OpenCodeAI] lane failed, trying Gemini refine:', e1?.message);
    }
    // Gemini / smart-engine lane (server)
    try {
      const j = await post('/api/generate.php', { action: 'refine', current_html: currentHtml, instruction: userPrompt });
      if (j && j.success && j.html) {
        return { conversation: j.response_msg || '✨ Applied via server AI.', isEdit: true, updatedHtml: j.html, engine: j.source || 'gemini' };
      }
      if (j && j.html && j.success === false) throw new Error(j.error || 'refine failed');
    } catch (e2) {
      laneErrs.push(e2?.message || 'refine failed');
      console.warn('[OpenCodeAI] refine lane failed:', e2?.message);
    }
    const err = new Error('All AI lanes failed (' + laneErrs.slice(0, 2).join(' | ').slice(0, 220) + '). Try a template action (e.g. "Add a pricing table with 3 plans") or switch model to ★ Space Bunny.');
    err.lanes = laneErrs;
    throw err;
  }

  const service = {
    FREE_MODELS, VARIATIONS,
    selectedModel: getModel(),
    getModel, setModel, getModels,
    analyzeRequirements, generateOne, generateQuick, generateConcepts, generateSubDesigns,
    chatAndEdit, editSnippet, editWithFallback, enrichTanglish, refreshModelDropdown
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { try { refreshModelDropdown(); } catch (e) {} });
  } else {
    try { refreshModelDropdown(); } catch (e) {}
  }
  return service;
})();
