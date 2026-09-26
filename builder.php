<?php
require_once __DIR__ . '/config.php';
$page_title = 'AI Website Builder — 3 Style Variations & AI Co-Pilot';
$page_desc  = 'Describe your business, choose a mode and a design direction, and get 3 AI-generated style variations.';
require_once __DIR__ . '/includes/nav.php';
?>
<style>
/* ══ BASE ══ */
body { background:#0a0d14; color:#e2e8f0; font-family:'Inter',system-ui,sans-serif; }

/* ══ RESUME BANNER ══ */
.resume-banner {
  display: none; position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
  z-index: 9998; max-width: min(680px, calc(100vw - 2rem));
  padding: 0.85rem 1rem 0.85rem 1.1rem;
  background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(99,102,241,0.12));
  border: 1.5px solid rgba(16,185,129,0.5);
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05) inset;
  align-items: center; gap: 0.85rem;
  animation: bannerIn 0.4s cubic-bezier(0.22,1,0.36,1);
}
.resume-banner.show { display: flex; }
@keyframes bannerIn { from { opacity: 0; transform: translate(-50%, -12px); } to { opacity: 1; transform: translate(-50%, 0); } }
.resume-banner .rb-icon { font-size: 1.4rem; flex-shrink: 0; }
.resume-banner .rb-text { flex: 1; min-width: 0; font-size: 0.84rem; color: #e2e8f0; line-height: 1.4; }
.resume-banner .rb-text strong { color: #34d399; font-weight: 800; }
.resume-banner .rb-text small { display: block; color: #94a3b8; font-size: 0.72rem; margin-top: 0.15rem; }
.resume-banner .rb-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }
.resume-banner .rb-btn {
  padding: 0.45rem 0.85rem; border-radius: 8px; border: 1.5px solid #334155;
  background: #0b0f17; color: #cbd5e1; font-family: inherit;
  font-size: 0.74rem; font-weight: 700; cursor: pointer; transition: all 0.15s;
}
.resume-banner .rb-btn:hover { border-color: #6366f1; color: #fff; }
.resume-banner .rb-btn.primary {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-color: #818cf8; color: #fff;
}
.resume-banner .rb-btn.danger {
  border-color: #7f1d1d; color: #fca5a5;
}
.resume-banner .rb-btn.danger:hover { background: #7f1d1d; color: #fff; }

/* ══ WIZARD SCREEN ══ */
#wizard-screen { min-height: calc(100vh - 64px); background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #0a0d14 70%); display: flex; align-items: center; justify-content: center; padding: 3rem 1.25rem; }
.wizard-card { background: #111622; border: 1px solid #1e293b; border-radius: 24px; width: 100%; max-width: 880px; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.05); overflow: hidden; }
.wizard-header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 2.25rem 2.5rem; color: #fff; }
.wizard-header h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.4rem; letter-spacing: -0.02em; }
.wizard-header p  { color: rgba(255,255,255,0.85); font-size: 0.95rem; }
.wizard-progress { display: flex; gap: 0.5rem; padding: 1.25rem 2.5rem; background: #0d121c; border-bottom: 1px solid #1e293b; }
.wp-dot { flex: 1; height: 5px; border-radius: 999px; background: #1e293b; transition: all 0.3s; }
.wp-dot.done { background: #10b981; }
.wp-dot.active { background: linear-gradient(90deg, #6366f1, #a855f7); box-shadow: 0 0 10px rgba(99,102,241,0.5); }
.wizard-body { padding: 2.25rem 2.5rem; }
.wizard-step { display: none; }
.wizard-step.active { display: block; animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.wiz-section-label { font-size: 0.78rem; font-weight: 700; color: #818cf8; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
.w-group { margin-bottom: 1.4rem; }
.w-label { display: block; font-size: 0.88rem; font-weight: 600; color: #f1f5f9; margin-bottom: 0.45rem; }
.w-label .req { color: #f43f5e; margin-left: 0.2rem; }
.w-hint { font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem; line-height: 1.4; }
.w-input, .w-select, .w-textarea { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #283347; border-radius: 10px; font-family: inherit; font-size: 0.92rem; color: #f8fafc; background: #0b0f17; transition: border-color 0.2s, box-shadow 0.2s; -webkit-appearance: none; }
.w-textarea { resize: vertical; min-height: 90px; }
.w-input:focus, .w-select:focus, .w-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.25); background: #111726; }
.w-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.1rem; padding-right: 2.5rem; }
.w-select option { background: #111622; color: #f8fafc; }
.w-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 520px) { .w-row { grid-template-columns: 1fr; } }
.style-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
@media (max-width: 480px) { .style-grid { grid-template-columns: repeat(2, 1fr); } }
.style-card { position: relative; }
.style-card input { position: absolute; opacity: 0; width: 0; height: 0; }
.style-card label { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; padding: 0.85rem 0.5rem; border: 1.5px solid #283347; border-radius: 12px; background: #0b0f17; cursor: pointer; text-align: center; font-size: 0.8rem; font-weight: 600; color: #94a3b8; transition: all 0.2s; }
.style-card input:checked + label { border-color: #6366f1; background: #1e1b4b; color: #a5b4fc; box-shadow: 0 0 15px rgba(99,102,241,0.25); }
.style-card label:hover { border-color: #6366f1; }
.color-grid { display: flex; flex-wrap: wrap; gap: 0.65rem; }
.color-swatch { position: relative; }
.color-swatch input { position: absolute; opacity: 0; }
.color-swatch label { display: block; width: 42px; height: 42px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: transform 0.2s, border-color 0.2s; }
.color-swatch input:checked + label { border-color: #fff; transform: scale(1.15); box-shadow: 0 0 12px rgba(255,255,255,0.4); }
.check-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
@media (max-width: 480px) { .check-grid { grid-template-columns: 1fr; } }
.wcheck { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.85rem; border: 1.5px solid #283347; border-radius: 10px; background: #0b0f17; cursor: pointer; transition: all 0.2s; }
.wcheck:hover { border-color: #6366f1; }
.wcheck input { accent-color: #6366f1; width: 17px; height: 17px; }
.wcheck span { font-size: 0.85rem; color: #cbd5e1; }
.wcheck:has(input:checked) { border-color: #6366f1; background: #181938; }
.wizard-footer { padding: 1.5rem 2.5rem; background: #0d121c; border-top: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.wbtn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 10px; font-family: inherit; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.wbtn-ghost { background: transparent; color: #94a3b8; border: 1.5px solid #283347; }
.wbtn-ghost:hover { color: #fff; border-color: #64748b; }
.wbtn-primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; box-shadow: 0 4px 15px rgba(79,70,229,0.4); }
.wbtn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.5); }
.wbtn-generate { background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 0.95rem; font-weight: 700; padding: 0.85rem 1.75rem; box-shadow: 0 4px 20px rgba(16,185,129,0.4); }
.wbtn-generate:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(16,185,129,0.55); }
.wbtn-generate:disabled { opacity: 0.55; cursor: not-allowed; }
.wbtn-clear { background: transparent; color: #fca5a5; border: 1.5px solid #7f1d1d; padding: 0.6rem 1rem; font-size: 0.82rem; }
.wbtn-clear:hover { background: #7f1d1d; color: #fff; }
.account-bar { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.85rem 1rem; background: #0a0f1c; border: 1.5px solid #1e293b; border-radius: 12px; margin-bottom: 1.25rem; flex-wrap: wrap; }
.account-info { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }
.account-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #7c3aed); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; }
.account-name { font-size: 0.85rem; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.account-status { font-size: 0.7rem; color: #10b981; font-weight: 600; margin-top: 0.1rem; }
.account-status.offline { color: #94a3b8; }
.account-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.account-btn { padding: 0.4rem 0.85rem; border-radius: 8px; border: 1.5px solid #334155; background: #0b0f17; color: #cbd5e1; font-family: inherit; font-size: 0.74rem; font-weight: 700; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.3rem; }
.account-btn:hover { border-color: #6366f1; color: #fff; }
.account-btn.danger { border-color: #7f1d1d; color: #fca5a5; }
.account-btn.danger:hover { background: #7f1d1d; color: #fff; }
.account-btn.primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); border-color: #818cf8; color: #fff; }
.gen-block { margin-top: 1.75rem; padding: 1.75rem; background: linear-gradient(135deg, rgba(16,185,129,0.06), rgba(99,102,241,0.06)); border: 1.5px solid rgba(16,185,129,0.35); border-radius: 16px; text-align: center; }
.gen-block h3 { font-size: 1.05rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; }
.gen-block p  { font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.25rem; line-height: 1.5; }
.gen-progress { display: none; margin-top: 1rem; padding: 1rem 1.25rem; background: #0a0f1c; border: 1.5px solid #1e293b; border-radius: 12px; }
.gen-progress.active { display: block; }
.gen-progress-bar { height: 6px; background: #1e293b; border-radius: 999px; overflow: hidden; margin-bottom: 0.6rem; }
.gen-progress-fill { height: 100%; width: 0%; background: linear-gradient(90deg, #6366f1, #a855f7); transition: width 0.4s ease; }
.gen-progress-msg { font-size: 0.82rem; color: #cbd5e1; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.gen-progress-msg .spinner { width: 14px; height: 14px; border: 2px solid #334155; border-top-color: #818cf8; border-radius: 50%; animation: spin 0.8s linear infinite; flex-shrink: 0; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ══ DESIGNS SCREEN ══ */
#designs-screen { display: none; min-height: calc(100vh - 64px); padding: 3rem 1.5rem 5rem; background: radial-gradient(circle at 50% 10%, #1e1b4b 0%, #0a0d14 60%); }
.designs-header { text-align: center; max-width: 780px; margin: 0 auto 3rem; }
.designs-badge { display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #a5b4fc; padding: 0.35rem 0.9rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; margin-bottom: 1rem; }
.designs-header h1 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: #fff; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
.designs-header p { font-size: 1.05rem; color: #94a3b8; line-height: 1.6; }
.designs-grid { max-width: 1300px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
@media (max-width: 990px) { .designs-grid { grid-template-columns: 1fr; max-width: 650px; } }
.design-card { background: #111622; border: 1.5px solid #283347; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 15px 40px rgba(0,0,0,0.4); transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s; position: relative; }
.design-card:hover { transform: translateY(-6px); border-color: #6366f1; box-shadow: 0 20px 50px rgba(99,102,241,0.25); }
.design-badge-top { position: absolute; top: 1rem; left: 1rem; z-index: 10; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.15); color: #38bdf8; font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.75rem; border-radius: 999px; }
.design-number-badge { position: absolute; top: 1rem; right: 1rem; z-index: 10; width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; font-size: 0.82rem; font-weight: 900; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(79,70,229,0.5); }
.design-preview-box { height: 300px; background: #0b0f17; position: relative; overflow: hidden; border-bottom: 1px solid #1e293b; }
.design-preview-iframe { width: 1400px; height: 900px; border: none; transform: scale(0.33); transform-origin: top left; pointer-events: none; }
.design-preview-box::after { content: '👁 Click "Full Preview" to see it live'; position: absolute; bottom: 0; left: 0; right: 0; padding: 1.2rem 1rem 0.6rem; background: linear-gradient(to top, rgba(11,15,23,0.95), transparent); color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-align: center; pointer-events: none; }
.design-content { padding: 1.75rem; display: flex; flex-direction: column; flex: 1; }
.design-content h3 { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
.design-content p { font-size: 0.88rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; flex: 1; }
.design-actions { display: grid; grid-template-columns: 1fr 1.4fr; gap: 0.75rem; }
.btn-preview-modal, .btn-choose-design { padding: 0.75rem; border-radius: 10px; font-weight: 600; font-size: 0.85rem; cursor: pointer; text-align: center; transition: all 0.2s; }
.btn-preview-modal { border: 1.5px solid #283347; background: #0b0f17; color: #cbd5e1; }
.btn-preview-modal:hover { border-color: #6366f1; color: #fff; }
.btn-choose-design { border: none; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; font-weight: 700; box-shadow: 0 4px 15px rgba(79,70,229,0.35); }
.btn-choose-design:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.5); }
.btn-admin-preview { margin-top: 0.65rem; padding: 0.7rem; border-radius: 10px; border: 1.5px solid #0891b2; background: rgba(8,145,178,0.12); color: #67e8f9; font-weight: 700; font-size: 0.82rem; cursor: pointer; width: 100%; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; }
.btn-admin-preview:hover { background: rgba(8,145,178,0.25); border-color: #06b6d4; color: #fff; }

/* ══ BUILDER SCREEN ══ */
#builder-screen { display: none; height: calc(100vh - 64px); flex-direction: column; background: #090d14; }
.builder-toolbar { min-height: 56px; background: #101522; border-bottom: 1px solid #1e293b; display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; flex-shrink: 0; flex-wrap: wrap; }
.tb-title { font-weight: 800; font-size: 0.9rem; color: #818cf8; display: flex; align-items: center; gap: 0.4rem; white-space: nowrap; }
.tb-site-badge { background: #1e1b4b; border: 1px solid rgba(99,102,241,0.3); color: #c7d2fe; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tb-divider { width: 1px; height: 24px; background: #1e293b; }
.design-tabs { display: flex; gap: 0.35rem; }
.d-tab { padding: 0.35rem 0.75rem; border-radius: 7px; border: 1px solid #283347; background: #0b0f17; color: #94a3b8; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.d-tab.active { background: #4f46e5; border-color: #6366f1; color: #fff; box-shadow: 0 0 10px rgba(99,102,241,0.4); }
.view-tabs { display: none; gap: 0.3rem; padding: 3px; background: #080c14; border-radius: 9px; border: 1px solid #283347; }
.view-tabs.show { display: flex; }
.view-tab { padding: 0.32rem 0.85rem; border-radius: 7px; border: none; background: transparent; color: #94a3b8; font-size: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.15s; white-space: nowrap; }
.view-tab.active { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; }
.view-tab.admin-view.active { background: linear-gradient(135deg, #0891b2, #0369a1); }
.tb-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.42rem 0.8rem; border-radius: 7px; font-family: inherit; font-size: 0.78rem; font-weight: 600; border: 1px solid #283347; background: #0b0f17; color: #cbd5e1; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.tb-btn:hover { background: #1e293b; border-color: #64748b; }
.tb-btn.visual-btn { background: linear-gradient(135deg, #4f46e5, #7c3aed); border: 1px solid #818cf8; color: #fff; font-weight: 700; }
.tb-btn.undo-btn { background: #2d1b4e; color: #e9d5ff; border-color: #6b21a8; }
.tb-btn.next-btn { background: linear-gradient(135deg, #10b981, #059669); border: 1px solid #34d399; color: #fff; font-weight: 800; }
.tb-btn.publish-btn { background: linear-gradient(135deg, #f59e0b, #d97706); border: 1px solid #fbbf24; color: #fff; font-weight: 800; box-shadow: 0 4px 14px rgba(245,158,11,0.4); }
.tb-btn.publish-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,0.55); }
.builder-main { display: flex; flex: 1; overflow: hidden; }
.preview-pane { flex: 1; display: flex; flex-direction: column; background: #0d121c; width: 100%; height: 100%; }
.preview-bar { background: #0d121c; border-bottom: 1px solid #1e293b; padding: 0 1rem; height: 42px; display: flex; align-items: center; justify-content: space-between; }
.device-toggles { display: flex; gap: 0.3rem; }
.device-btn { padding: 0.28rem 0.6rem; border: 1px solid #283347; border-radius: 6px; background: transparent; color: #94a3b8; cursor: pointer; font-size: 0.75rem; font-family: inherit; }
.device-btn.active { color: #fff; border-color: #6366f1; background: #1e1b4b; }
.preview-container { flex: 1; display: flex; justify-content: center; background: #06090e; overflow: hidden; }
#live-iframe { width: 100%; height: 100%; border: none; background: #fff; transition: width 0.3s ease; }
.preview-container.tablet #live-iframe { width: 768px; }
.preview-container.mobile #live-iframe { width: 390px; }

/* ══ FLOATING AI BUTTON ══ */
.floating-gemini-btn { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1000; display: inline-flex; align-items: center; gap: 0.55rem; padding: 0.68rem 1.25rem 0.68rem 0.9rem; border-radius: 999px; border: 1px solid rgba(129,140,248,0.35); background: linear-gradient(135deg, #4f46e5 0%, #6d5ce8 55%, #8b5cf6 100%); color: #fff; font-family: inherit; font-size: 0.85rem; font-weight: 700; letter-spacing: -0.005em; box-shadow: 0 10px 28px rgba(79,70,229,0.45), 0 0 0 1px rgba(255,255,255,0.06) inset; cursor: pointer; transition: transform 0.22s cubic-bezier(0.22,1,0.36,1), box-shadow 0.22s; }
.floating-gemini-btn::before { content: ''; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(135deg, rgba(255,255,255,0.18), transparent 55%); pointer-events: none; }
.floating-gemini-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 36px rgba(79,70,229,0.6), 0 0 0 1px rgba(255,255,255,0.1) inset; }
.floating-gemini-btn:active { transform: translateY(0) scale(0.98); }
.floating-gemini-btn .fg-orb { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: rgba(255,255,255,0.18); font-size: 0.82rem; line-height: 1; position: relative; }
.floating-gemini-btn .fg-orb::after { content: ''; position: absolute; inset: -3px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.35); animation: orbPulse 2s ease-in-out infinite; }
@keyframes orbPulse { 0%,100% { transform: scale(1); opacity: 0.6; } 50% { transform: scale(1.15); opacity: 0; } }

/* ══ AI CHAT PANEL ══ */
.magic-ai-panel { position: fixed; bottom: 5.5rem; right: 1.5rem; width: min(420px, calc(100vw - 2rem)); height: min(620px, calc(100vh - 8rem)); background: #0c1220; border: 1px solid #1e293b; border-radius: 20px; box-shadow: 0 24px 70px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.03) inset; z-index: 1001; display: none; flex-direction: column; overflow: hidden; font-family: 'Inter', system-ui, sans-serif; }
.magic-ai-panel.active { display: flex; animation: magicPanelIn 0.28s cubic-bezier(0.22,1,0.36,1); }
@keyframes magicPanelIn { from { opacity: 0; transform: translateY(14px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
.magic-header { padding: 0.85rem 0.9rem 0.85rem 1rem; background: linear-gradient(180deg, #121a2e 0%, #0d1424 100%); border-bottom: 1px solid #1c2740; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-shrink: 0; position: relative; }
.magic-header::after { content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 1px; background: linear-gradient(90deg, transparent, rgba(129,140,248,0.45), transparent); }
.magic-brand { display: flex; align-items: center; gap: 0.65rem; min-width: 0; }
.magic-avatar { position: relative; width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #4f46e5, #7c3aed 55%, #a855f7); box-shadow: 0 4px 14px rgba(124,58,237,0.45); font-size: 1rem; color: #fff; }
.magic-avatar::after { content: ''; position: absolute; right: -2px; bottom: -2px; width: 10px; height: 10px; border-radius: 50%; background: #10b981; border: 2px solid #0d1424; box-shadow: 0 0 8px rgba(16,185,129,0.8); }
.magic-brand-text { display: flex; flex-direction: column; min-width: 0; }
.magic-brand-name { font-size: 0.86rem; font-weight: 800; color: #fff; letter-spacing: -0.01em; line-height: 1.15; }
.magic-brand-sub { font-size: 0.68rem; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 0.3rem; margin-top: 1px; }
.magic-brand-sub .dot { width: 5px; height: 5px; border-radius: 50%; background: #10b981; box-shadow: 0 0 6px #10b981; }
.magic-header-actions { display: flex; align-items: center; gap: 0.25rem; flex-shrink: 0; }
.magic-icon-btn { width: 28px; height: 28px; border-radius: 8px; border: none; background: transparent; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: all 0.15s; font-family: inherit; line-height: 1; }
.magic-icon-btn:hover { background: #1a2338; color: #fff; }
.puter-model-row { display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.85rem; background: #080c14; border-bottom: 1px solid #162032; font-size: 0.7rem; gap: 0.5rem; flex-shrink: 0; }
.puter-model-select { background: #111726; border: 1px solid #283347; color: #e2e8f0; border-radius: 6px; font-size: 0.7rem; padding: 0.2rem 0.45rem; font-family: inherit; outline: none; }
.magic-chat-log { flex: 1; overflow-y: auto; padding: 1rem 0.9rem 1.1rem; display: flex; flex-direction: column; gap: 0.9rem; scroll-behavior: smooth; background: radial-gradient(120% 60% at 50% 0%, rgba(79,70,229,0.06), transparent 60%); }
.magic-chat-log::-webkit-scrollbar { width: 6px; }
.magic-chat-log::-webkit-scrollbar-thumb { background: #243049; border-radius: 999px; }
.msg { display: flex; gap: 0.55rem; align-items: flex-end; max-width: 100%; animation: msgIn 0.3s cubic-bezier(0.22,1,0.36,1); }
@keyframes msgIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.msg.user { flex-direction: row-reverse; }
.msg-avatar { width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 800; line-height: 1; }
.msg.ai .msg-avatar { background: linear-gradient(135deg, #4f46e5, #a855f7); color: #fff; box-shadow: 0 3px 10px rgba(124,58,237,0.35); }
.msg.user .msg-avatar { background: #1e293b; color: #c7d2fe; border: 1px solid #334155; }
.msg-body { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; max-width: calc(100% - 42px); }
.msg.user .msg-body { align-items: flex-end; }
.msg-bubble { position: relative; padding: 0.6rem 0.85rem; border-radius: 14px; font-size: 0.82rem; line-height: 1.55; color: #e2e8f0; word-wrap: break-word; overflow-wrap: anywhere; }
.msg.ai .msg-bubble { background: #141c2f; border: 1px solid #1e293b; border-bottom-left-radius: 5px; }
.msg.user .msg-bubble { background: linear-gradient(135deg, #4f46e5, #6d5ce8); color: #fff; border-bottom-right-radius: 5px; box-shadow: 0 4px 14px rgba(79,70,229,0.32); }
.msg-bubble strong { color: #fff; font-weight: 800; }
.msg-bubble a { color: #a5b4fc; }
.msg-bubble code { background: #1e293b; padding: 0.1rem 0.35rem; border-radius: 4px; color: #a5b4fc; font-size: 0.75rem; }
.msg-meta { display: flex; align-items: center; gap: 0.35rem; font-size: 0.62rem; color: #64748b; font-weight: 600; padding: 0 0.25rem; }
.msg-copy { opacity: 0; background: transparent; border: none; color: #64748b; font-size: 0.62rem; font-weight: 700; cursor: pointer; padding: 0.1rem 0.35rem; border-radius: 4px; transition: all 0.15s; font-family: inherit; }
.msg:hover .msg-copy { opacity: 1; }
.msg-copy:hover { background: #1e293b; color: #c7d2fe; }
.typing-dots { display: inline-flex; gap: 0.28rem; align-items: center; padding: 0.1rem 0; }
.typing-dots i { width: 6px; height: 6px; border-radius: 50%; background: #818cf8; display: block; animation: tdWave 1.2s infinite ease-in-out; }
.typing-dots i:nth-child(2) { animation-delay: 0.15s; }
.typing-dots i:nth-child(3) { animation-delay: 0.3s; }
@keyframes tdWave { 0%,60%,100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-5px); opacity: 1; } }
.m-chip-row { display: flex; gap: 0.4rem; overflow-x: auto; padding: 0.65rem 0.9rem; border-top: 1px solid #1c2740; background: #0a0f1c; flex-shrink: 0; scrollbar-width: none; }
.m-chip-row::-webkit-scrollbar { display: none; }
.m-chip { padding: 0.36rem 0.75rem; border-radius: 999px; border: 1px solid #243049; background: #0f1729; color: #cbd5e1; font-size: 0.72rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.16s; display: inline-flex; align-items: center; gap: 0.3rem; font-family: inherit; }
.m-chip:hover { border-color: #6366f1; color: #fff; background: #1a2140; transform: translateY(-1px); }
.magic-input-row { display: flex; gap: 0.5rem; padding: 0.7rem 0.9rem 0.5rem; background: #0a0f1c; flex-shrink: 0; align-items: flex-end; flex-wrap: wrap; }
.magic-input-wrap { flex: 1 1 200px; min-width: 0; display: flex; align-items: center; gap: 0.5rem; background: #080c14; border: 1.5px solid #243049; border-radius: 12px; padding: 0.05rem 0.2rem 0.05rem 0.75rem; transition: border-color 0.18s, box-shadow 0.18s; position: relative; }
.magic-input-wrap:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.16); }
.magic-input-icon { color: #64748b; font-size: 0.85rem; flex-shrink: 0; line-height: 1; }
.magic-input { flex: 1; min-width: 0; padding: 0.62rem 0; border: none; background: transparent; color: #fff; font-family: inherit; font-size: 0.84rem; outline: none; }
.magic-input::placeholder { color: #475569; }
.magic-btn { width: 38px; height: 38px; border-radius: 11px; border: none; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer; flex-shrink: 0; box-shadow: 0 4px 14px rgba(79,70,229,0.4); transition: all 0.18s; line-height: 1; }
.magic-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.6); }
.magic-btn:disabled { opacity: 0.55; cursor: wait; transform: none; }
.magic-mic-btn { width: 38px; height: 38px; border-radius: 11px; border: 1.5px solid #243049; background: #080c14; color: #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; transition: all 0.15s; line-height: 1; }
.magic-mic-btn:hover { border-color: #6366f1; color: #fff; }
.magic-mic-btn.recording { background: linear-gradient(135deg, #dc2626, #ef4444); border-color: #f87171; color: #fff; animation: micPulse 1.2s ease-in-out infinite; }
@keyframes micPulse { 0%,100% { box-shadow: 0 0 0 4px rgba(239,68,68,0.25); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0.05); } }
.magic-lang-select { height: 38px; padding: 0 0.5rem; border-radius: 11px; border: 1.5px solid #243049; background: #080c14; color: #cbd5e1; font-family: inherit; font-size: 0.7rem; font-weight: 600; cursor: pointer; flex-shrink: 0; }
.magic-hint { font-size: 0.65rem; color: #475569; text-align: center; padding: 0 0.9rem 0.7rem; background: #0a0f1c; font-weight: 600; }
.magic-hint kbd { background: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 0.05rem 0.32rem; font-family: inherit; font-size: 0.62rem; color: #cbd5e1; }
.voice-listening-toast { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 0.6rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; border-radius: 999px; font-size: 0.72rem; font-weight: 700; box-shadow: 0 8px 24px rgba(239,68,68,0.4); display: none; white-space: nowrap; z-index: 100; align-items: center; gap: 0.5rem; }
.voice-listening-toast.show { display: flex; }
.voice-listening-toast::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #fff; animation: blink 0.9s infinite; }
@keyframes blink { 50% { opacity: 0.2; } }
@media (max-width: 480px) {
  .magic-ai-panel { right: 0.75rem; left: 0.75rem; width: auto; bottom: 5rem; }
  .floating-gemini-btn span:not(.fg-orb) { display: none; }
  .floating-gemini-btn { padding: 0.7rem; }
}

/* ══ MODALS ══ */
.modal-overlay { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); align-items: center; justify-content: center; padding: 2rem; }
.modal-overlay.active { display: flex; }
.modal-box { background: #111622; border: 1.5px solid #283347; border-radius: 20px; width: 90%; max-width: 1200px; height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.8); }
.modal-bar { padding: 1rem 1.5rem; background: #0d121c; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.modal-iframe { flex: 1; border: none; background: #fff; }
.mode-grid { padding: 1.5rem; display: grid; gap: 1rem; grid-template-columns: repeat(3, 1fr); }
@media (max-width: 720px) { .mode-grid { grid-template-columns: 1fr; } }
.gen-mode-card { border: 2px solid #283347; border-radius: 16px; padding: 1.5rem 1.25rem; background: #0b0f17; cursor: pointer; transition: all 0.25s; text-align: left; font-family: inherit; color: inherit; position: relative; }
.gen-mode-card:hover { border-color: #6366f1; transform: translateY(-3px); box-shadow: 0 12px 32px rgba(99,102,241,0.25); }
.gen-mode-card.selected { border-color: #10b981; background: linear-gradient(180deg,#062b22,#0b0f17); }
.gen-mode-card[data-mode="admin"].selected { border-color: #6366f1; background: linear-gradient(180deg,#1e1b4b,#0b0f17); }
.gen-mode-card[data-mode="database"].selected { border-color: #0891b2; background: linear-gradient(180deg,#082f49,#0b0f17); }
.gen-mode-card .gm-icon { font-size: 1.9rem; display: block; margin-bottom: 0.5rem; }
.gen-mode-card h3 { font-size: 1.05rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; }
.gen-mode-card p { font-size: 0.8rem; color: #94a3b8; line-height: 1.5; margin-bottom: 0.6rem; }
.gen-mode-card .gm-foot { font-size: 0.7rem; color: #818cf8; font-weight: 700; }
.gen-mode-card .gm-badge { position: absolute; top: 0.7rem; right: 0.7rem; background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: 0.58rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 999px; letter-spacing: 0.05em; text-transform: uppercase; }
.gen-mode-card[data-mode="static"] .gm-badge { background: linear-gradient(135deg,#10b981,#059669); }
.gen-mode-card[data-mode="database"] .gm-badge { background: linear-gradient(135deg,#0891b2,#0369a1); }
.toast { position: fixed; bottom: 1.5rem; left: 1.5rem; z-index: 10000; background: #111622; border: 1.5px solid #283347; border-radius: 12px; padding: 0.85rem 1.4rem; color: #fff; font-size: 0.88rem; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.6); transform: translateY(100px); opacity: 0; transition: all 0.3s ease; max-width: 90vw; }
.toast.show { transform: translateY(0); opacity: 1; }

/* ══ ADMIN REQUIREMENTS (in modal) ══ */
.admin-req-section { display: none; padding: 1.5rem; border-top: 1px solid #1e293b; background: linear-gradient(180deg, #0a0f1c, #0d121c); }
.admin-req-section.active { display: block; animation: fadeIn 0.3s ease; }
.admin-req-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.admin-req-header .ar-icon { font-size: 1.6rem; }
.admin-req-header h3 { font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 0.15rem; }
.admin-req-header p { font-size: 0.78rem; color: #94a3b8; }
.admin-req-textarea { width: 100%; padding: 0.85rem 1rem; border: 1.5px solid #283347; border-radius: 12px; background: #080c14; color: #fff; font-family: inherit; font-size: 0.85rem; line-height: 1.6; resize: vertical; min-height: 140px; }
.admin-req-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.2); }
.admin-preset-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.75rem; }
.admin-preset-btn { padding: 0.42rem 0.85rem; border-radius: 9px; border: 1.5px solid #334155; background: #0b0f17; color: #cbd5e1; font-family: inherit; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.3rem; }
.admin-preset-btn:hover { border-color: #6366f1; color: #fff; background: #1a1f36; transform: translateY(-1px); }
.admin-ai-note { display: flex; gap: 0.6rem; align-items: flex-start; padding: 0.85rem 1rem; background: rgba(99,102,241,0.08); border: 1.5px solid rgba(99,102,241,0.3); border-radius: 10px; margin-top: 1rem; font-size: 0.78rem; color: #c7d2fe; line-height: 1.5; }
.admin-ai-note .note-icon { font-size: 1.1rem; flex-shrink: 0; }
</style>

<!-- ═══ RESUME BANNER ═══ -->
<div class="resume-banner" id="resume-banner">
  <div class="rb-icon">✨</div>
  <div class="rb-text">
    <strong>Welcome back!</strong> Resumed your last session.
    <small id="resume-details">—</small>
  </div>
  <div class="rb-actions">
    <button class="rb-btn" onclick="dismissResumeBanner()">Dismiss</button>
    <button class="rb-btn danger" onclick="startFreshFromBanner()">Start Fresh</button>
  </div>
</div>

<!-- ═══ WIZARD SCREEN ═══ -->
<div id="wizard-screen">
  <div class="wizard-card">
    <div class="wizard-header">
      <h1>✦ AI Website Generator</h1>
      <p>Tell us about your business and design direction. We'll generate <strong>3 style variations</strong> — you pick your favorite.</p>
    </div>

    <div class="wizard-progress">
      <div class="wp-dot active" id="wp1"></div>
      <div class="wp-dot" id="wp2"></div>
      <div class="wp-dot" id="wp3"></div>
      <div class="wp-dot" id="wp4"></div>
    </div>

    <div class="wizard-body">
      <!-- STEP 1 -->
      <div class="wizard-step active" id="step1">
        <div class="wiz-section-label">Step 1 of 4 · Business Profile</div>
        <div class="w-row">
          <div class="w-group">
            <label class="w-label" for="biz_name">Business / Brand Name <span class="req">*</span></label>
            <input class="w-input" type="text" id="biz_name" placeholder="e.g. Apex Digital" value="Zenith Studio">
          </div>
          <div class="w-group">
            <label class="w-label" for="biz_type">Industry / Category <span class="req">*</span></label>
            <select class="w-select" id="biz_type">
              <option>Creative &amp; Digital Agency</option>
              <option>Restaurant, Cafe &amp; Bar</option>
              <option>Tech Startup &amp; SaaS</option>
              <option>Professional Services &amp; Legal</option>
              <option>Healthcare &amp; Wellness Clinic</option>
              <option>Real Estate &amp; Architecture</option>
              <option>Fitness Center &amp; Personal Trainer</option>
              <option>E-Commerce &amp; Retail</option>
              <option>Personal Portfolio &amp; Creator</option>
              <option>School / College / Education</option>
              <option>Other</option>
            </select>
          </div>
        </div>
        <div class="w-group">
          <label class="w-label" for="biz_tagline">Core Mission / Tagline <span class="req">*</span></label>
          <input class="w-input" type="text" id="biz_tagline" value="Crafting next-generation digital experiences that inspire and scale.">
        </div>
        <div class="w-group">
          <label class="w-label" for="biz_audience">Target Audience</label>
          <input class="w-input" type="text" id="biz_audience" value="High-growth brands and modern businesses seeking top-tier digital quality.">
        </div>
        <div class="w-group">
          <label class="w-label" for="biz_services">Services / Products Offered</label>
          <input class="w-input" type="text" id="biz_services" value="UI/UX Design, Full-Stack Web Development, Brand Identity, Growth Optimization">
        </div>
      </div>

      <!-- STEP 2 -->
      <div class="wizard-step" id="step2">
        <div class="wiz-section-label">Step 2 of 4 · Primary Design Direction</div>
        <p class="w-hint" style="margin-bottom:1.5rem;">
          Tell us the aesthetic you want. <strong style="color:#a5b4fc">All 3 style variations we generate will follow this direction</strong> — only the layout approach will differ.
        </p>
        <div class="w-group">
          <label class="w-label">Design Aesthetic <span class="req">*</span></label>
          <div class="style-grid">
            <div class="style-card"><input type="radio" name="design_style" id="st_modern" value="modern" checked><label for="st_modern"><span style="font-size:1.4rem">🎯</span>Modern &amp; Clean</label></div>
            <div class="style-card"><input type="radio" name="design_style" id="st_bold" value="bold"><label for="st_bold"><span style="font-size:1.4rem">⚡</span>Bold &amp; Dynamic</label></div>
            <div class="style-card"><input type="radio" name="design_style" id="st_dark" value="dark"><label for="st_dark"><span style="font-size:1.4rem">🌙</span>Luxury &amp; Dark</label></div>
          </div>
        </div>
        <div class="w-group">
          <label class="w-label" for="design_direction">Describe your design direction (optional)</label>
          <textarea class="w-textarea" id="design_direction" rows="3" placeholder="e.g. Minimal Scandinavian with warm neutrals, generous whitespace, editorial typography…"></textarea>
          <div class="w-hint">Free-text adds extra guidance on top of the aesthetic above.</div>
        </div>
        <div class="w-group">
          <label class="w-label">Brand Color Accent</label>
          <div class="color-grid">
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_purple" value="purple" checked><label for="cp_purple" style="background:linear-gradient(135deg,#6366f1,#a855f7)"></label></div>
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_blue" value="blue"><label for="cp_blue" style="background:linear-gradient(135deg,#2563eb,#06b6d4)"></label></div>
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_green" value="green"><label for="cp_green" style="background:linear-gradient(135deg,#059669,#10b981)"></label></div>
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_red" value="red"><label for="cp_red" style="background:linear-gradient(135deg,#dc2626,#f97316)"></label></div>
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_gold" value="gold"><label for="cp_gold" style="background:linear-gradient(135deg,#d97706,#f59e0b)"></label></div>
            <div class="color-swatch"><input type="radio" name="color_palette" id="cp_slate" value="slate"><label for="cp_slate" style="background:linear-gradient(135deg,#1e293b,#475569)"></label></div>
          </div>
        </div>
      </div>

      <!-- STEP 3 -->
      <div class="wizard-step" id="step3">
        <div class="wiz-section-label">Step 3 of 4 · Website Sections</div>
        <div class="w-group">
          <label class="w-label">Include Sections</label>
          <div class="check-grid">
            <label class="wcheck"><input type="checkbox" name="sections[]" value="hero" checked><span>Hero &amp; Value Proposition</span></label>
            <label class="wcheck"><input type="checkbox" name="sections[]" value="services" checked><span>Services &amp; Offerings</span></label>
            <label class="wcheck"><input type="checkbox" name="sections[]" value="about" checked><span>About &amp; Story</span></label>
            <label class="wcheck"><input type="checkbox" name="sections[]" value="metrics" checked><span>Key Metrics &amp; Stats</span></label>
            <label class="wcheck"><input type="checkbox" name="sections[]" value="contact" checked><span>Interactive Contact Section</span></label>
            <label class="wcheck"><input type="checkbox" name="sections[]" value="footer" checked><span>Footer with Links</span></label>
          </div>
        </div>
      </div>

      <!-- STEP 4 -->
      <div class="wizard-step" id="step4">
        <div class="wiz-section-label">Step 4 of 4 · Contact Info &amp; Generate</div>
        <div class="account-bar" id="account-bar">
          <div class="account-info">
            <div class="account-avatar" id="account-avatar">?</div>
            <div>
              <div class="account-name" id="account-name">Not signed in</div>
              <div class="account-status offline" id="account-status">Sign in to enable AI generation</div>
            </div>
          </div>
          <div class="account-actions" id="account-actions">
            <button class="account-btn primary" onclick="handlePuterSignIn()">✦ Sign In</button>
            <button class="account-btn" onclick="handlePuterCreateAccount()">+ Create Free Account</button>
          </div>
        </div>

        <div class="w-row">
          <div class="w-group"><label class="w-label" for="biz_phone">Phone Number</label><input class="w-input" type="text" id="biz_phone" value="+1 (555) 890-1234"></div>
          <div class="w-group"><label class="w-label" for="biz_email">Contact Email</label><input class="w-input" type="email" id="biz_email" value="hello@zenithstudio.com"></div>
        </div>
        <div class="w-group"><label class="w-label" for="biz_address">Headquarters / Location</label><input class="w-input" type="text" id="biz_address" value="450 Innovation Parkway, San Francisco, CA"></div>

        <div class="gen-block">
          <h3>✦ Ready to Generate</h3>
          <p>We'll create <strong style="color:#a5b4fc">3 distinct style variations</strong> based on your design direction.<br>Each variation uses your exact content — only the layout differs.</p>
          <button class="wbtn wbtn-generate" id="wiz-generate-btn" onclick="openGenerateModal()" disabled>
            <span>✦</span><span>Generate 3 Style Variations</span>
          </button>
        </div>

        <div class="gen-progress" id="gen-progress">
          <div class="gen-progress-bar"><div class="gen-progress-fill" id="gen-progress-fill"></div></div>
          <div class="gen-progress-msg"><span class="spinner"></span><span id="gen-progress-text">Ready</span></div>
        </div>
      </div>
    </div>

    <div class="wizard-footer">
      <button class="wbtn wbtn-ghost" id="wiz-prev" onclick="wizNav(-1)" disabled>&larr; Back</button>
      <div id="wiz-step-counter" style="font-size:0.85rem; color:#64748b; font-weight:600">Step 1 of 4</div>
      <button class="wbtn wbtn-primary" id="wiz-next" onclick="wizNav(1)">Next Step &rarr;</button>
      <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
        <button class="wbtn wbtn-clear" id="wiz-clear" onclick="clearAndSwitchAccount()" style="display:none;">✕ Clear</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ DESIGNS SCREEN ═══ -->
<div id="designs-screen">
  <div class="designs-header">
    <div class="designs-badge">✦ 3 Style Variations</div>
    <h1 id="designs-title">Choose Your Style Variation</h1>
    <p id="designs-sub">All 3 use your exact content and design direction — only the layout differs.</p>
  </div>
  <div class="designs-grid" id="designs-grid"></div>
</div>

<!-- ═══ LIVE WORKSPACE ═══ -->
<div id="builder-screen">
  <div class="builder-toolbar">
    <div class="tb-title">✦ WebCraft AI</div>
    <div class="tb-site-badge" id="tb-biz-badge">Zenith Studio</div>
    <div class="tb-divider"></div>
    <div class="design-tabs">
      <button class="d-tab active" id="tab-d0" onclick="switchActiveDesign(0)">Variation 1</button>
      <button class="d-tab" id="tab-d1" onclick="switchActiveDesign(1)">Variation 2</button>
      <button class="d-tab" id="tab-d2" onclick="switchActiveDesign(2)">Variation 3</button>
    </div>
    <div class="view-tabs" id="view-tabs">
      <button class="view-tab active" id="vtab-site" onclick="switchWorkspaceView('site')">🌐 Site</button>
      <button class="view-tab admin-view" id="vtab-admin" onclick="switchWorkspaceView('admin')">🔐 Admin Panel</button>
    </div>
    <div class="tb-divider"></div>
    <button class="tb-btn" onclick="openInNewTab()">👁 Preview</button>
    <button class="tb-btn visual-btn" onclick="openStudioInNewTab()">🎨 Edit in Studio ↗</button>
    <button class="tb-btn" onclick="backToDesigns()">← Back to Variations</button>
    <div style="flex:1"></div>
    <button class="tb-btn undo-btn" id="btn-undo-ai" onclick="undoLastAiChange()" style="display:none">↶ Undo AI</button>
    <button class="tb-btn publish-btn" id="btn-publish" onclick="saveAndProceedToPayment()">🚀 Save &amp; Publish</button>
  </div>

  <div class="builder-main">
    <div class="preview-pane">
      <div class="preview-bar">
        <div class="device-toggles">
          <button class="device-btn active" id="d-desktop" onclick="setDevice('desktop')">🖥 Desktop</button>
          <button class="device-btn" id="d-tablet" onclick="setDevice('tablet')">📱 Tablet</button>
          <button class="device-btn" id="d-mobile" onclick="setDevice('mobile')">📲 Mobile</button>
        </div>
        <div style="display:flex; align-items:center; gap:0.6rem;">
          <span id="current-view-label" style="font-size:0.72rem;color:#94a3b8;font-weight:700;">🌐 Frontend Site</span>
          <button class="tb-btn" onclick="refreshLivePreview()" style="padding:0.28rem 0.7rem; font-size:0.75rem;">↺ Reload</button>
        </div>
      </div>
      <div class="preview-container" id="preview-container">
        <iframe id="live-iframe" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- ═══ FLOATING AI BUTTON ═══ -->
<button class="floating-gemini-btn" id="floating-ai-btn" onclick="toggleMagicAi()">
  <span class="fg-orb">✦</span>
  <span>WebCraft AI</span>
</button>

<div class="magic-ai-panel" id="magic-ai-panel">
  <div class="magic-header">
    <div class="magic-brand">
      <div class="magic-avatar">✦</div>
      <div class="magic-brand-text">
        <div class="magic-brand-name">WebCraft AI Co-Pilot</div>
        <div class="magic-brand-sub"><span class="dot"></span> Online · <span id="ai-model-label">DeepSeek V3</span></div>
      </div>
    </div>
    <div class="magic-header-actions">
      <button class="magic-icon-btn" onclick="clearChatLog()" title="Clear chat">🗑</button>
      <button class="magic-icon-btn" onclick="toggleMagicAi()" title="Close">✕</button>
    </div>
  </div>

  <div class="puter-model-row">
    <div style="display:flex; align-items:center; gap:0.4rem;">
      <span style="color:#818cf8; font-weight:700;">Model:</span>
      <select class="puter-model-select" id="ai-model-select" onchange="changeAiModel(this.value)">
        <option value="deepseek/deepseek-chat" selected>DeepSeek V3 (Free)</option>
        <option value="gpt-4o-mini">GPT-4o Mini</option>
        <option value="claude-3-5-sonnet">Claude 3.5 Sonnet</option>
        <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
      </select>
    </div>
  </div>

  <div class="magic-chat-log" id="ai-chat-log">
    <div class="msg ai">
      <div class="msg-avatar">✦</div>
      <div class="msg-body">
        <div class="msg-bubble">
          👋 <strong>Hello! I'm your AI Co-Pilot</strong><br>
          Talk to me in <strong>English</strong>, <strong>Tanglish</strong>, or Tamil.<br>
          Use the <strong>🎤</strong> button to speak instead of typing.
        </div>
        <div class="msg-meta">AI · just now</div>
      </div>
    </div>
  </div>

  <div class="m-chip-row" id="magic-chips-container">
    <span class="m-chip" onclick="quickRefine('Add a modern 5-star testimonials section')">⭐ Reviews</span>
    <span class="m-chip" onclick="quickRefine('Add a modern pricing table with 3 plans')">💰 Pricing</span>
    <span class="m-chip" onclick="quickRefine('Add an interactive FAQ accordion')">❓ FAQ</span>
    <span class="m-chip" onclick="quickRefine('Add a floating WhatsApp chat button')">💬 WhatsApp</span>
    <span class="m-chip" onclick="quickRefine('Add meet our leadership team section')">👥 Team</span>
    <span class="m-chip" onclick="quickRefine('Switch theme to sleek dark mode')">🌙 Dark Mode</span>
  </div>

  <div class="magic-input-row">
    <div class="magic-input-wrap">
      <span class="magic-input-icon">✦</span>
      <input type="text" class="magic-input" id="refine-query" placeholder="Ask AI anything — English or Tanglish…">
    </div>
    <button class="magic-mic-btn" id="btn-voice" onclick="toggleVoiceInput()" title="Voice chat"><span id="mic-icon">🎤</span></button>
    <select class="magic-lang-select" id="voice-lang" title="Voice language">
      <option value="ta-IN">🇮🇳 Tamil</option>
      <option value="en-IN" selected>🇮🇳 EN-IN</option>
      <option value="en-US">🇺🇸 EN-US</option>
      <option value="hi-IN">🇮🇳 Hindi</option>
    </select>
    <button class="magic-btn" id="btn-refine" onclick="executeRefine()" title="Send">➤</button>
  </div>

  <div class="magic-hint">Press <kbd>Enter</kbd> to send · Tanglish OK · 🎤 voice input</div>
</div>

<!-- ═══ FULLSCREEN PREVIEW MODAL ═══ -->
<div class="modal-overlay" id="fullscreen-modal">
  <div class="modal-box">
    <div class="modal-bar">
      <div style="font-weight:700; color:#fff" id="modal-title">Fullscreen Preview</div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <div class="view-tabs show" id="modal-view-tabs" style="display:none;">
          <button class="view-tab active" id="m-vtab-site" onclick="switchModalView('site')">🌐 Site</button>
          <button class="view-tab admin-view" id="m-vtab-admin" onclick="switchModalView('admin')">🔐 Admin</button>
        </div>
        <button class="wbtn wbtn-primary" onclick="selectFromModal()">🚀 Select &amp; Edit</button>
        <button class="wbtn wbtn-ghost" onclick="closeFullscreenModal()">✕ Close</button>
      </div>
    </div>
    <iframe class="modal-iframe" id="modal-iframe" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
  </div>
</div>

<!-- ═══ GENERATE MODE MODAL ═══ -->
<div class="modal-overlay" id="generate-modal">
  <div class="modal-box" style="max-width:960px; height:auto; max-height:90vh; overflow-y:auto;">
    <div class="modal-bar">
      <div>
        <div style="font-weight:800; color:#fff; font-size:1.05rem;">✦ Choose Generation Mode</div>
        <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.15rem;">
          Each mode produces <strong style="color:#a5b4fc">3 AI-generated style variations</strong> — all following your design direction.
        </div>
      </div>
      <button class="wbtn wbtn-ghost" onclick="closeGenerateModal()">✕ Close</button>
    </div>
    <div class="mode-grid">
      <button class="gen-mode-card selected" data-mode="static" onclick="pickGenerateMode('static', this)">
        <span class="gm-badge">Simplest</span>
        <span class="gm-icon">📄</span>
        <h3>Static</h3>
        <p>HTML / CSS / JS only.<br>No backend, no admin, no DB.</p>
        <div class="gm-foot">▸ 3 style variations</div>
      </button>
      <button class="gen-mode-card" data-mode="admin" onclick="pickGenerateMode('admin', this)">
        <span class="gm-badge">With CMS</span>
        <span class="gm-icon">🛠️</span>
        <h3>Admin</h3>
        <p>Site + AI-written admin panel.<br>JSON storage — no database.</p>
        <div class="gm-foot">▸ 3 style variations</div>
      </button>
      <button class="gen-mode-card" data-mode="database" onclick="pickGenerateMode('database', this)">
        <span class="gm-badge">Full Stack</span>
        <span class="gm-icon">🗄️</span>
        <h3>Admin + Database</h3>
        <p>Site + AI admin + MySQL.<br>Full CRUD, session auth.</p>
        <div class="gm-foot">▸ 3 style variations</div>
      </button>
    </div>

    <!-- ═══ ADMIN REQUIREMENTS (only shown when admin/database picked) ═══ -->
    <div class="admin-req-section" id="admin-req-section">
      <div class="admin-req-header">
        <div class="ar-icon">🧠</div>
        <div>
          <h3>AI Will Write Your Admin Panel</h3>
          <p>No templates. Just describe what you need to manage — the AI will write every PHP file from scratch.</p>
        </div>
      </div>

      <div class="w-group" style="margin-bottom:1rem;">
        <label class="w-label" for="admin_requirements">What should your admin panel manage? <span class="req">*</span></label>
        <textarea class="admin-req-textarea" id="admin_requirements" rows="6"
          placeholder="Examples for a school:
- Manage students (name, roll no, class, section, parent name, phone, address, photo)
- Manage teachers (name, subject, email, phone)
- Manage classes
- Track fees paid by each student
- Record daily attendance

Be specific about fields you need!"></textarea>
      </div>

      <div class="w-group" style="margin-bottom:0;">
        <label class="w-label">⚡ Quick presets (click to auto-fill)</label>
        <div class="admin-preset-row">
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('school')">🎓 School</button>
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('restaurant')">🍽️ Restaurant</button>
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('clinic')">🏥 Clinic</button>
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('gym')">💪 Gym</button>
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('shop')">🛒 Shop</button>
          <button type="button" class="admin-preset-btn" onclick="fillAdminPreset('realestate')">🏠 Real Estate</button>
        </div>
      </div>

      <div class="admin-ai-note">
        <span class="note-icon">💡</span>
        <div>
          <strong>How it works:</strong> After you publish, the AI generates your complete admin panel (login, dashboard, forms, tables).
          You'll see the full file tree and can even <em>fix any file with AI</em> before paying.
        </div>
      </div>
    </div>

    <div class="modal-bar" style="border-top:1px solid #1e293b; border-bottom:none; justify-content:space-between;">
      <div style="font-size:0.76rem; color:#94a3b8;">
        Mode: <strong id="gen-modal-mode-label" style="color:#10b981;">Static</strong>
        <span id="gen-modal-account" style="margin-left:0.6rem;">—</span>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <button class="wbtn wbtn-ghost" onclick="handlePuterSignIn()">↻ Switch Account</button>
        <button class="wbtn wbtn-primary" onclick="confirmGenerate()"><span>✦</span><span>Generate Now</span></button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"><span id="toast-text"></span></div>

<script src="https://js.puter.com/v2/"></script>
<script src="<?= SITE_URL ?>/assets/js/puter-service.js"></script>
<script src="<?= SITE_URL ?>/assets/js/puter-website-generator.js"></script>
<script>
/* ══════════════════════════════════════════════════
   STATE
═════════════════════════════════════════════════ */
let generatedDesigns = [];
let activeDesignIndex = 0;
let currentHtml = '';
let currentViewMode = 'site';
let modalViewingIndex = 0;
let modalViewMode = 'site';
let selectedGenMode = 'static';
let generatedConcepts = [];
let currentAuthUser = null;
const HISTORY_LIMIT = 5;
const SESSION_KEY = 'webcraft_saved_project';

const ADMIN_PRESETS = {
  school: `Manage students (name, roll number, class, section, parent name, phone, address, photo, admission date)
Manage teachers (name, subject, email, phone, joined date)
Manage classes (class name, class teacher, room, no. of students)
Track fees paid by each student (student name, amount, paid date, method, notes)
Record daily attendance (student, date, status Present/Absent/Late, remarks)`,
  restaurant: `Manage menu items (name, category, price, description, photo, available yes/no)
Manage table reservations (guest name, phone, date, time, no. of guests, notes)
Manage incoming orders (customer name, items, total, status Pending/Preparing/Ready/Completed)`,
  clinic: `Manage patients (name, age, gender, phone, address, diagnosis)
Manage appointments (patient, doctor, date, time, status Scheduled/Completed/Cancelled)
Manage prescriptions (patient, doctor, medicines, date)
Manage doctors (name, specialization, email, phone)`,
  gym: `Manage members (name, phone, plan Monthly/Quarterly/Yearly, joined date, expires on)
Manage classes (class name, trainer, schedule, capacity)
Manage trainers (name, specialization, phone, email)`,
  shop: `Manage products (name, category, price, stock, description, photo)
Manage orders (customer name, items, total, status Pending/Shipped/Delivered)
Manage customers (name, phone, email, address)
Manage categories (name, description)`,
  realestate: `Manage properties (title, type Apartment/Villa/Plot/Commercial, price, location, area sqft, description, photo)
Manage inquiries (name, phone, property, message)
Manage agents (name, phone, email, specialization)`
};

/* ══════════════════════════════════════════════════
   INIT
═════════════════════════════════════════════════ */
window.addEventListener('DOMContentLoaded', async () => {
  await refreshAccountUI();
  window.addEventListener('puter-auth-changed', () => refreshAccountUI());

  const restored = tryRestoreSession();

  if (!restored) {
    updateWizDisplay();
  } else {
    updateWizDisplay();
    updateGenerateButton();
  }

  const sel = document.getElementById('ai-model-select');
  if (sel && window.PuterService?.selectedModel) sel.value = window.PuterService.selectedModel;
  updateModelLabel();

  wireAutoSave();

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeFullscreenModal();
      closeGenerateModal();
      stopVoiceInput();
    }
  });

  window.addEventListener('beforeunload', () => {
    saveSessionNow();
  });
});

/* ══════════════════════════════════════════════════
   SESSION PERSISTENCE
═════════════════════════════════════════════════ */
function collectWizardSnapshot() {
  const sections = Array.from(document.querySelectorAll('input[name="sections[]"]:checked')).map(c => c.value);
  return {
    biz_name:         document.getElementById('biz_name')?.value.trim() || '',
    biz_type:         document.getElementById('biz_type')?.value || '',
    biz_tagline:      document.getElementById('biz_tagline')?.value.trim() || '',
    biz_audience:     document.getElementById('biz_audience')?.value.trim() || '',
    biz_services:     document.getElementById('biz_services')?.value.trim() || '',
    design_style:     document.querySelector('input[name="design_style"]:checked')?.value || 'modern',
    design_direction: document.getElementById('design_direction')?.value.trim() || '',
    color_palette:    document.querySelector('input[name="color_palette"]:checked')?.value || 'purple',
    biz_phone:        document.getElementById('biz_phone')?.value.trim() || '',
    biz_email:        document.getElementById('biz_email')?.value.trim() || '',
    biz_address:      document.getElementById('biz_address')?.value.trim() || '',
    admin_requirements: document.getElementById('admin_requirements')?.value.trim() || '',
    sections
  };
}

function saveSessionNow() {
  try {
    let stage = 'wizard';
    if (document.getElementById('builder-screen')?.style.display === 'flex') stage = 'builder';
    else if (document.getElementById('designs-screen')?.style.display === 'block') stage = 'designs';

    const payload = {
      version: 3,
      savedAt: Date.now(),
      stage,
      wizard: collectWizardSnapshot(),
      selectedGenMode,
      currentWizStep,
      bizName: document.getElementById('biz_name')?.value.trim() || 'Website',
      concepts: generatedConcepts,
      designs: generatedDesigns,
      activeDesignIndex,
      currentViewMode
    };
    localStorage.setItem(SESSION_KEY, JSON.stringify(payload));
  } catch (e) { console.warn('[session save]', e); }
}

function loadSession() {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (!data || (data.version !== 2 && data.version !== 3)) return null;
    return data;
  } catch (e) { return null; }
}

function tryRestoreSession() {
  const saved = loadSession();
  if (!saved) return false;

  const hasConcepts = Array.isArray(saved.concepts) && saved.concepts.length > 0;
  const hasDesigns  = Array.isArray(saved.designs)  && saved.designs.length  > 0;

  if (saved.wizard) {
    const w = saved.wizard;
    const set = (id, val) => { const el = document.getElementById(id); if (el && val != null) el.value = val; };
    set('biz_name', w.biz_name);
    set('biz_type', w.biz_type);
    set('biz_tagline', w.biz_tagline);
    set('biz_audience', w.biz_audience);
    set('biz_services', w.biz_services);
    set('design_direction', w.design_direction);
    set('biz_phone', w.biz_phone);
    set('biz_email', w.biz_email);
    set('biz_address', w.biz_address);
    set('admin_requirements', w.admin_requirements);

    if (w.design_style) {
      const r = document.querySelector(`input[name="design_style"][value="${w.design_style}"]`);
      if (r) r.checked = true;
    }
    if (w.color_palette) {
      const r = document.querySelector(`input[name="color_palette"][value="${w.color_palette}"]`);
      if (r) r.checked = true;
    }
    if (Array.isArray(w.sections)) {
      document.querySelectorAll('input[name="sections[]"]').forEach(cb => {
        cb.checked = w.sections.includes(cb.value);
      });
    }
  }

  if (saved.selectedGenMode) selectedGenMode = saved.selectedGenMode;
  if (saved.currentWizStep)  currentWizStep  = saved.currentWizStep;

  if (!hasConcepts && !hasDesigns) {
    updateWizDisplay();
    return false;
  }

  generatedConcepts = saved.concepts || [];
  generatedDesigns  = saved.designs  || [];
  activeDesignIndex = saved.activeDesignIndex || 0;
  currentViewMode   = saved.currentViewMode || 'site';
  currentWizStep    = 4;

  if (hasDesigns && saved.stage === 'builder') {
    restoreIntoWorkspace();
    showResumeBanner('Resumed your editing session · ' + generatedDesigns.length + ' variations');
    return true;
  }

  if (hasConcepts) {
    display3Designs(generatedConcepts, saved.bizName || 'Your Website');
    showResumeBanner('Resumed — 3 variations ready to preview');
    return true;
  }

  return false;
}

function restoreIntoWorkspace() {
  const c = generatedDesigns[activeDesignIndex];
  if (!c) return;

  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'none';
  document.getElementById('builder-screen').style.display = 'flex';
  document.getElementById('tb-biz-badge').textContent = document.getElementById('biz_name').value || 'Website';

  [0, 1, 2].forEach(i => document.getElementById(`tab-d${i}`)?.classList.toggle('active', i === activeDesignIndex));
  updateViewTabsVisibility();

  document.getElementById('vtab-site').classList.toggle('active', currentViewMode === 'site');
  document.getElementById('vtab-admin').classList.toggle('active', currentViewMode === 'admin');
  document.getElementById('current-view-label').textContent = currentViewMode === 'admin' ? '🔐 Admin Panel' : '🌐 Frontend Site';

  if (currentViewMode === 'admin') {
    updateLiveIframe(stabilizeAdminHtml(c.adminHtml));
  } else {
    currentHtml = c.html;
    updateLiveIframe(currentHtml);
  }
  updateUndoBtn();
}

function wireAutoSave() {
  let timer = null;
  const scheduleSave = () => {
    clearTimeout(timer);
    timer = setTimeout(saveSessionNow, 400);
  };
  document.querySelectorAll(
    '.wizard-body input, .wizard-body select, .wizard-body textarea, .admin-req-textarea'
  ).forEach(el => {
    el.addEventListener('input', scheduleSave);
    el.addEventListener('change', scheduleSave);
  });
}

function showResumeBanner(details) {
  const banner = document.getElementById('resume-banner');
  if (!banner) return;
  const det = document.getElementById('resume-details');
  if (det) det.textContent = details + ' · Auto-saved';
  banner.classList.add('show');
  setTimeout(() => banner.classList.remove('show'), 8000);
}
function dismissResumeBanner() {
  document.getElementById('resume-banner')?.classList.remove('show');
}
async function startFreshFromBanner() {
  if (!confirm('⚠️ Start fresh? This will delete your saved work.')) return;
  dismissResumeBanner();
  try { localStorage.removeItem(SESSION_KEY); } catch (e) {}
  generatedConcepts = [];
  generatedDesigns = [];
  activeDesignIndex = 0;
  currentHtml = '';
  currentWizStep = 1;
  selectedGenMode = 'static';
  document.getElementById('designs-grid').innerHTML = '';
  document.getElementById('gen-progress').classList.remove('active');
  document.getElementById('wizard-screen').style.display = '';
  document.getElementById('designs-screen').style.display = 'none';
  document.getElementById('builder-screen').style.display = 'none';
  updateWizDisplay();
  showToast('🧹 Fresh start — fill the wizard again');
}

/* ══════════════════════════════════════════════════
   WIZARD
═════════════════════════════════════════════════ */
let currentWizStep = 1;
function wizNav(dir) {
  if (dir === 1 && !validateStep(currentWizStep)) return;
  currentWizStep = Math.max(1, Math.min(4, currentWizStep + dir));
  updateWizDisplay();
  saveSessionNow();
}
function validateStep(step) {
  if (step === 1) {
    const n = document.getElementById('biz_name').value.trim();
    const t = document.getElementById('biz_tagline').value.trim();
    if (!n) { showToast('⚠️ Enter Business Name'); document.getElementById('biz_name').focus(); return false; }
    if (!t) { showToast('⚠️ Enter Core Mission / Tagline'); document.getElementById('biz_tagline').focus(); return false; }
  }
  return true;
}
function updateWizDisplay() {
  document.querySelectorAll('.wizard-step').forEach((s, i) => s.classList.toggle('active', i + 1 === currentWizStep));
  ['wp1','wp2','wp3','wp4'].forEach((id, i) => {
    const d = document.getElementById(id);
    if (!d) return;
    d.classList.toggle('active', i + 1 === currentWizStep);
    d.classList.toggle('done', i + 1 < currentWizStep);
  });
  document.getElementById('wiz-prev').disabled = (currentWizStep === 1);
  document.getElementById('wiz-step-counter').textContent = `Step ${currentWizStep} of 4`;
  document.getElementById('wiz-next').style.display = (currentWizStep === 4) ? 'none' : 'inline-flex';
  const cb = document.getElementById('wiz-clear');
  if (cb) cb.style.display = (currentAuthUser && currentWizStep === 4) ? 'inline-flex' : 'none';
  updateGenerateButton();
}
function updateGenerateButton() {
  const b = document.getElementById('wiz-generate-btn');
  if (!b) return;
  b.disabled = !currentAuthUser;
  b.style.opacity = currentAuthUser ? '1' : '0.55';
}

/* ══════════════════════════════════════════════════
   PUTER AUTH
═════════════════════════════════════════════════ */
async function refreshAccountUI() {
  const av = document.getElementById('account-avatar');
  const nm = document.getElementById('account-name');
  const st = document.getElementById('account-status');
  const ac = document.getElementById('account-actions');
  const cb = document.getElementById('wiz-clear');
  let state = { isSignedIn: false, user: null };
  try { if (window.WebsiteGenerator) state = await window.WebsiteGenerator.getAuthState(); } catch (e) {}
  currentAuthUser = state.user;
  if (state.isSignedIn && state.user) {
    if (av) av.textContent = (state.user.username || 'U')[0].toUpperCase();
    if (nm) nm.textContent = '@' + (state.user.username || 'User');
    if (st) { st.textContent = '🟢 Connected to Puter'; st.className = 'account-status'; }
    if (ac) ac.innerHTML = `<button class="account-btn" onclick="handlePuterSwitchAccount()">↻ Switch Account</button><button class="account-btn danger" onclick="handlePuterSignOut()">Sign Out</button>`;
    if (cb) cb.style.display = (currentWizStep === 4) ? 'inline-flex' : 'none';
  } else {
    if (av) av.textContent = '?';
    if (nm) nm.textContent = 'Not signed in';
    if (st) { st.textContent = 'Sign in to enable AI generation'; st.className = 'account-status offline'; }
    if (ac) ac.innerHTML = `<button class="account-btn primary" onclick="handlePuterSignIn()">✦ Sign In</button><button class="account-btn" onclick="handlePuterCreateAccount()">+ Create Free Account</button>`;
    if (cb) cb.style.display = 'none';
  }
  updateGenerateButton();
}
async function handlePuterSignIn() { if (!window.WebsiteGenerator) return; try { showToast('Opening Puter sign-in…'); await window.WebsiteGenerator.signIn(); await refreshAccountUI(); showToast('✓ Signed in!'); } catch (e) { showToast('Sign-in cancelled'); } }
async function handlePuterCreateAccount() { if (!window.WebsiteGenerator) return; try { await window.WebsiteGenerator.signIn(); await refreshAccountUI(); showToast('✓ Account ready!'); } catch (e) { showToast('Sign-up cancelled'); } }
async function handlePuterSwitchAccount() { if (!window.WebsiteGenerator) return; try { await window.WebsiteGenerator.switchAccount(); await refreshAccountUI(); showToast('✓ Switched!'); } catch (e) { showToast('Switch cancelled'); } }
async function handlePuterSignOut() { if (!window.WebsiteGenerator) return; window.WebsiteGenerator.signOut(); window.WebsiteGenerator.clearLocalState(); currentAuthUser = null; await refreshAccountUI(); showToast('Signed out.'); }

async function clearAndSwitchAccount() {
  if (!confirm('⚠️ Clear session, sign out, and start fresh?\n\nThis wipes drafts too.')) return;
  try { window.WebsiteGenerator?.clearLocalState?.(); window.WebsiteGenerator?.clearGeneratedProjects?.(); } catch (e) {}
  try { await window.WebsiteGenerator?.signOut?.(); } catch (e) {}
  try { localStorage.removeItem(SESSION_KEY); } catch (e) {}
  generatedConcepts = []; generatedDesigns = []; currentAuthUser = null;
  currentWizStep = 1; activeDesignIndex = 0; currentHtml = ''; selectedGenMode = 'static';
  document.getElementById('designs-grid').innerHTML = '';
  document.getElementById('gen-progress').classList.remove('active');
  document.getElementById('wizard-screen').style.display = '';
  document.getElementById('designs-screen').style.display = 'none';
  document.getElementById('builder-screen').style.display = 'none';
  document.getElementById('refine-query').value = '';
  document.getElementById('magic-ai-panel').classList.remove('active');
  dismissResumeBanner();
  updateWizDisplay();
  await refreshAccountUI();
  showToast('🧹 Cleared. Please sign in.');
  setTimeout(() => handlePuterSignIn(), 400);
}

/* ══════════════════════════════════════════════════
   GENERATE MODAL
═════════════════════════════════════════════════ */
function openGenerateModal() {
  if (!currentAuthUser) { showToast('⚠️ Sign in first'); return; }
  const acc = document.getElementById('gen-modal-account');
  if (acc) acc.textContent = '· Signed in as @' + (currentAuthUser.username || 'you');
  document.querySelectorAll('#generate-modal .gen-mode-card').forEach(c => c.classList.toggle('selected', c.dataset.mode === selectedGenMode));
  document.getElementById('gen-modal-mode-label').textContent = labelFor(selectedGenMode);
  updateAdminReqSection();
  document.getElementById('generate-modal').classList.add('active');
}
function closeGenerateModal() { document.getElementById('generate-modal').classList.remove('active'); }
function pickGenerateMode(mode, btn) {
  selectedGenMode = mode;
  document.querySelectorAll('#generate-modal .gen-mode-card').forEach(c => c.classList.toggle('selected', c === btn));
  document.getElementById('gen-modal-mode-label').textContent = labelFor(mode);
  updateAdminReqSection();
}
function updateAdminReqSection() {
  const s = document.getElementById('admin-req-section');
  if (!s) return;
  const needsAdmin = selectedGenMode !== 'static';
  s.classList.toggle('active', needsAdmin);
}
function labelFor(mode) { return { static: 'Static', admin: 'Admin Panel + PHP', database: 'Admin + PHP + MySQL' }[mode] || mode; }
function confirmGenerate() {
  if (selectedGenMode !== 'static') {
    const req = document.getElementById('admin_requirements')?.value.trim() || '';
    if (req.length < 20) {
      showToast('⚠️ Please describe what your admin panel should manage');
      document.getElementById('admin_requirements')?.focus();
      return;
    }
  }
  saveSessionNow();
  closeGenerateModal();
  generateWithMode(selectedGenMode);
}

function fillAdminPreset(key) {
  const t = document.getElementById('admin_requirements');
  if (t && ADMIN_PRESETS[key]) {
    t.value = ADMIN_PRESETS[key];
    t.focus();
    saveSessionNow();
    showToast('✓ Preset loaded — edit as needed');
  }
}

/* ══════════════════════════════════════════════════
   GENERATE
═════════════════════════════════════════════════ */
async function generateWithMode(mode) {
  if (!currentAuthUser) { showToast('⚠️ Sign in first'); return; }
  if (!window.WebsiteGenerator) { showToast('⚠️ Generator not loaded'); return; }
  selectedGenMode = mode;
  const data = collectWizardSnapshot();
  if (!data.biz_name || !data.biz_tagline) { showToast('⚠️ Complete Step 1 first'); return; }

  const progress = document.getElementById('gen-progress');
  const fill = document.getElementById('gen-progress-fill');
  const text = document.getElementById('gen-progress-text');
  progress.classList.add('active');
  fill.style.width = '5%'; fill.style.background = '';
  text.textContent = 'Preparing…';
  document.getElementById('wiz-generate-btn').disabled = true;
  document.getElementById('wiz-next').disabled = true;
  document.getElementById('wiz-prev').disabled = true;

  try {
    const concepts = await window.WebsiteGenerator.generateConcepts(data, mode, {
      model: window.PuterService?.selectedModel || 'deepseek/deepseek-chat',
      onProgress: (p) => {
        if (p.stage === 'connecting') { fill.style.width = '15%'; text.textContent = p.message; }
        else if (p.stage === 'generating') { fill.style.width = '50%'; text.textContent = p.message; }
        else if (p.stage === 'done') { fill.style.width = '100%'; text.textContent = p.message; }
      }
    });
    generatedConcepts = concepts;
    generatedDesigns = concepts.map(c => ({
      name: c.name, description: c.description, badge: c.badge, html: c.html,
      adminHtml: c.adminHtml || null, phpBackend: c.phpBackend || null,
      sqlSchema: c.sqlSchema || null, history: []
    }));
    activeDesignIndex = 0;
    currentHtml = generatedDesigns[0]?.html || '';

    saveSessionNow();

    display3Designs(concepts, data.biz_name);
    fill.style.width = '100%'; text.textContent = '✓ Complete!';
    setTimeout(() => progress.classList.remove('active'), 1500);
    showToast('✨ 3 style variations ready!');
  } catch (err) {
    console.error(err);
    fill.style.width = '100%';
    fill.style.background = 'linear-gradient(90deg,#ef4444,#dc2626)';
    text.textContent = '⚠️ ' + (err.message || 'Failed');
    showToast('Failed: ' + err.message);
  } finally {
    document.getElementById('wiz-generate-btn').disabled = false;
    document.getElementById('wiz-next').disabled = false;
    document.getElementById('wiz-prev').disabled = (currentWizStep === 1);
    updateGenerateButton();
  }
}

/* ══════════════════════════════════════════════════
   DISPLAY 3 DESIGNS
═════════════════════════════════════════════════ */
function display3Designs(concepts, bizName) {
  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'block';
  document.getElementById('builder-screen').style.display = 'none';
  document.getElementById('designs-title').textContent = `3 Style Variations for ${bizName}`;
  document.getElementById('designs-sub').textContent = selectedGenMode === 'static'
    ? 'All 3 use your exact content and design direction — only the layout differs.'
    : 'All 3 include an AI-written admin panel — pick the site style you like best.';
  const grid = document.getElementById('designs-grid');
  grid.innerHTML = '';
  concepts.forEach((c, i) => {
    const card = document.createElement('div');
    card.className = 'design-card';
    card.innerHTML = `
      <div class="design-badge-top">${escapeHtml(c.badge || `Variation ${i+1}`)}</div>
      <div class="design-number-badge">${i + 1}</div>
      <div class="design-preview-box"><iframe class="design-preview-iframe" id="d${i}-iframe"></iframe></div>
      <div class="design-content">
        <h3>${escapeHtml(c.name)}</h3>
        <p>${escapeHtml(c.description)}</p>
        <div class="design-actions">
          <button class="btn-preview-modal" onclick="openFullscreenModal(${i})">👁️ Full Preview</button>
          <button class="btn-choose-design" onclick="selectDesignAndEdit(${i})">Select &amp; Edit →</button>
        </div>
      </div>`;
    grid.appendChild(card);
    setTimeout(() => { const f = document.getElementById(`d${i}-iframe`); if (f) f.srcdoc = c.html; }, 40);
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
  saveSessionNow();
}

/* ══════════════════════════════════════════════════
   ADMIN PANEL STABILIZER
═════════════════════════════════════════════════ */
function stabilizeAdminHtml(html) {
  if (!html) return '';
  const stabilizer = `
<style id="__wc_admin_stabilizer__">
html, body {
  height: auto !important;
  min-height: 100% !important;
  overflow-x: hidden !important;
  overscroll-behavior: contain !important;
  scroll-behavior: auto !important;
}
body { position: relative !important; }
[style*="position:fixed"], [style*="position: fixed"],
[style*="position:sticky"], [style*="position: sticky"] {
  position: absolute !important;
}
aside, .sidebar, [class*="sidebar"], nav[class*="side"] {
  position: absolute !important;
}
*, *::before, *::after {
  animation-iteration-count: 1 !important;
  transition-duration: 0.001ms !important;
  scroll-behavior: auto !important;
}
[data-anim], [data-anim-trigger] {
  animation: none !important;
  transform: none !important;
  opacity: 1 !important;
  filter: none !important;
}
canvas, iframe { max-width: 100%; }
</style>
`;
  if (/<head[^>]*>/i.test(html)) {
    return html.replace(/<head([^>]*)>/i, `<head$1>${stabilizer}`);
  }
  return stabilizer + html;
}

/* ══════════════════════════════════════════════════
   FULLSCREEN MODAL
═════════════════════════════════════════════════ */
function openFullscreenModal(index) {
  modalViewingIndex = index; modalViewMode = 'site';
  const c = generatedConcepts[index];
  if (!c) return;
  document.getElementById('modal-title').textContent = c.name;
  const tabs = document.getElementById('modal-view-tabs');
  const hasAdmin = selectedGenMode !== 'static' && c.adminHtml;
  tabs.style.display = hasAdmin ? 'flex' : 'none';
  document.getElementById('m-vtab-site').classList.add('active');
  document.getElementById('m-vtab-admin').classList.remove('active');
  document.getElementById('modal-iframe').srcdoc = c.html;
  document.getElementById('fullscreen-modal').classList.add('active');
}
function switchModalView(view) {
  modalViewMode = view;
  const c = generatedConcepts[modalViewingIndex];
  if (!c) return;
  document.getElementById('m-vtab-site').classList.toggle('active', view === 'site');
  document.getElementById('m-vtab-admin').classList.toggle('active', view === 'admin');
  document.getElementById('modal-iframe').srcdoc = view === 'admin'
    ? stabilizeAdminHtml(c.adminHtml || '<p style="padding:2rem;font-family:sans-serif;">No admin panel yet — will be generated during publish.</p>')
    : c.html;
}
function closeFullscreenModal() { document.getElementById('fullscreen-modal').classList.remove('active'); }
function selectFromModal() { closeFullscreenModal(); selectDesignAndEdit(modalViewingIndex); }
function openAdminPreview(index) {
  const c = generatedConcepts[index];
  if (!c || !c.adminHtml) { showToast('Admin panel will be AI-generated at publish time'); return; }
  modalViewingIndex = index; modalViewMode = 'admin';
  document.getElementById('modal-title').textContent = `${c.name} — Admin Panel`;
  document.getElementById('modal-view-tabs').style.display = 'flex';
  document.getElementById('m-vtab-site').classList.remove('active');
  document.getElementById('m-vtab-admin').classList.add('active');
  document.getElementById('modal-iframe').srcdoc = stabilizeAdminHtml(c.adminHtml);
  document.getElementById('fullscreen-modal').classList.add('active');
}

/* ══════════════════════════════════════════════════
   SELECT DESIGN → WORKSPACE
═════════════════════════════════════════════════ */
function selectDesignAndEdit(index) {
  const c = generatedConcepts[index] || generatedDesigns[index];
  if (!c) return;

  if (!generatedDesigns.length) {
    generatedDesigns = generatedConcepts.map(x => ({
      name: x.name, description: x.description, badge: x.badge, html: x.html,
      adminHtml: x.adminHtml || null, phpBackend: x.phpBackend || null,
      sqlSchema: x.sqlSchema || null, history: []
    }));
  }

  activeDesignIndex = index;
  currentHtml = generatedDesigns[index].html;
  currentViewMode = 'site';
  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'none';
  document.getElementById('builder-screen').style.display = 'flex';
  document.getElementById('tb-biz-badge').textContent = document.getElementById('biz_name').value || 'Website';
  [0, 1, 2].forEach(i => document.getElementById(`tab-d${i}`)?.classList.toggle('active', i === index));
  updateViewTabsVisibility();
  document.getElementById('vtab-site').classList.add('active');
  document.getElementById('vtab-admin').classList.remove('active');
  document.getElementById('current-view-label').textContent = '🌐 Frontend Site';
  updateLiveIframe(currentHtml);
  updateUndoBtn();
  saveSessionNow();
  showToast(`✏️ Loaded "${c.name}"`);
}
function backToDesigns() {
  document.getElementById('builder-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'block';
  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('magic-ai-panel').classList.remove('active');
  saveSessionNow();
}
function updateViewTabsVisibility() {
  const c = generatedDesigns[activeDesignIndex];
  const hasAdmin = selectedGenMode !== 'static' && c?.adminHtml;
  document.getElementById('view-tabs').classList.toggle('show', !!hasAdmin);
}
function switchWorkspaceView(view) {
  const c = generatedDesigns[activeDesignIndex];
  if (!c) return;
  currentViewMode = view;
  document.getElementById('vtab-site').classList.toggle('active', view === 'site');
  document.getElementById('vtab-admin').classList.toggle('active', view === 'admin');
  document.getElementById('current-view-label').textContent = view === 'admin' ? '🔐 Admin Panel' : '🌐 Frontend Site';

  if (view === 'admin') {
    updateLiveIframe(stabilizeAdminHtml(c.adminHtml || ''));
  } else {
    updateLiveIframe(c.html);
  }
  saveSessionNow();
}
function switchActiveDesign(index) {
  if (!generatedDesigns[index]) return;
  activeDesignIndex = index;
  currentHtml = generatedDesigns[index].html;
  currentViewMode = 'site';
  [0, 1, 2].forEach(i => document.getElementById(`tab-d${i}`)?.classList.toggle('active', i === index));
  updateViewTabsVisibility();
  document.getElementById('vtab-site').classList.add('active');
  document.getElementById('vtab-admin').classList.remove('active');
  document.getElementById('current-view-label').textContent = '🌐 Frontend Site';
  updateLiveIframe(currentHtml);
  updateUndoBtn();
  saveSessionNow();
}
function updateLiveIframe(html) { const f = document.getElementById('live-iframe'); if (f) f.srcdoc = html; }
function refreshLivePreview() {
  const c = generatedDesigns[activeDesignIndex];
  if (!c) return;
  if (currentViewMode === 'admin') updateLiveIframe(stabilizeAdminHtml(c.adminHtml || ''));
  else updateLiveIframe(currentHtml);
  showToast('Preview refreshed');
}
function setDevice(device) {
  const c = document.getElementById('preview-container');
  c.className = 'preview-container' + (device !== 'desktop' ? ' ' + device : '');
  ['desktop', 'tablet', 'mobile'].forEach(d => document.getElementById(`d-${d}`)?.classList.toggle('active', d === device));
}
function openInNewTab() {
  const c = generatedDesigns[activeDesignIndex];
  const html = currentViewMode === 'admin' ? (c?.adminHtml || '') : currentHtml;
  window.open(URL.createObjectURL(new Blob([html], { type: 'text/html;charset=utf-8' })), '_blank');
}

/* ══════════════════════════════════════════════════
   UNDO
═════════════════════════════════════════════════ */
function pushHistory(i, html) {
  const d = generatedDesigns[i];
  if (!d) return;
  if (!Array.isArray(d.history)) d.history = [];
  if (d.history[d.history.length - 1] === html) return;
  d.history.push(html);
  if (d.history.length > HISTORY_LIMIT) d.history.shift();
}
function undoLastAiChange() {
  const d = generatedDesigns[activeDesignIndex];
  if (!d || !d.history || d.history.length === 0) { showToast('Nothing to undo'); return; }
  currentHtml = d.history.pop();
  d.html = currentHtml;
  if (currentViewMode === 'site') updateLiveIframe(currentHtml);
  updateUndoBtn();
  saveSessionNow();
  showToast('↶ Reverted');
  appendGeminiChatMessage('↶ Reverted the last AI edit.');
}
function updateUndoBtn() {
  const d = generatedDesigns[activeDesignIndex];
  const b = document.getElementById('btn-undo-ai');
  if (b) b.style.display = (d && d.history && d.history.length > 0) ? 'inline-flex' : 'none';
}

/* ══════════════════════════════════════════════════
   AI CHAT PANEL
═════════════════════════════════════════════════ */
function toggleMagicAi(forceOpen) {
  const panel = document.getElementById('magic-ai-panel');
  if (!panel) return;
  if (forceOpen === true) panel.classList.add('active');
  else panel.classList.toggle('active');
  if (panel.classList.contains('active')) {
    setTimeout(() => document.getElementById('refine-query')?.focus(), 200);
  }
}
function getTimeStr() { return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
function appendUserChatMessage(msg) {
  const log = document.getElementById('ai-chat-log');
  if (!log) return;
  const row = document.createElement('div');
  row.className = 'msg user';
  row.innerHTML = `<div class="msg-avatar">You</div><div class="msg-body"><div class="msg-bubble">${escapeHtml(msg)}</div><div class="msg-meta">${getTimeStr()}</div></div>`;
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}
function appendGeminiChatMessage(msg) {
  const log = document.getElementById('ai-chat-log');
  if (!log) return;
  const row = document.createElement('div');
  row.className = 'msg ai';
  row.innerHTML = `<div class="msg-avatar">✦</div><div class="msg-body"><div class="msg-bubble">${msg}</div><div class="msg-meta">AI · ${getTimeStr()}<button class="msg-copy" type="button" onclick="copyMsg(this)">Copy</button></div></div>`;
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}
function copyMsg(btn) {
  const bubble = btn.closest('.msg-body')?.querySelector('.msg-bubble');
  if (!bubble) return;
  const tmp = document.createElement('textarea');
  tmp.value = bubble.innerText;
  document.body.appendChild(tmp); tmp.select();
  try { document.execCommand('copy'); showToast('📋 Copied'); } catch (e) {}
  tmp.remove();
}
function clearChatLog() {
  const log = document.getElementById('ai-chat-log');
  if (!log || !confirm('Clear conversation?')) return;
  log.innerHTML = `<div class="msg ai"><div class="msg-avatar">✦</div><div class="msg-body"><div class="msg-bubble">👋 Chat cleared. What should I build next?</div><div class="msg-meta">AI · just now</div></div></div>`;
}
function escapeHtml(s) { return s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function formatMarkdown(t) {
  if (!t) return '';
  let s = escapeHtml(t);
  s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/\*(.*?)\*/g, '<em>$1</em>');
  s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
  s = s.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
  return s;
}
function quickRefine(t) {
  toggleMagicAi(true);
  document.getElementById('refine-query').value = t;
  setTimeout(executeRefine, 150);
}
function changeAiModel(m) { if (window.PuterService?.setModel) window.PuterService.setModel(m); updateModelLabel(); showToast(`Model: ${m.split('/').pop()}`); }
function updateModelLabel() {
  const s = document.getElementById('ai-model-select');
  const l = document.getElementById('ai-model-label');
  if (s && l) l.textContent = s.options[s.selectedIndex]?.text || 'DeepSeek V3';
}

/* ══════════════════════════════════════════════════
   VOICE
═════════════════════════════════════════════════ */
let voiceRecognition = null;
let isRecording = false;
function initVoiceRecognition() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) return null;
  const r = new SR();
  r.continuous = false; r.interimResults = true; r.maxAlternatives = 1;
  r.onstart = () => {
    isRecording = true;
    document.getElementById('btn-voice')?.classList.add('recording');
    document.getElementById('mic-icon').textContent = '⏹';
    showListeningToast(true);
  };
  r.onresult = (e) => {
    let interim = '', final = '';
    for (let i = e.resultIndex; i < e.results.length; i++) {
      const t = e.results[i][0].transcript;
      if (e.results[i].isFinal) final += t; else interim += t;
    }
    const input = document.getElementById('refine-query');
    if (input) input.value = (final || interim).trim();
    if (final) setTimeout(() => { stopVoiceInput(); executeRefine(); }, 350);
  };
  r.onerror = (e) => {
    if (e.error === 'not-allowed') showToast('🎤 Mic permission denied');
    else if (e.error === 'no-speech') showToast('🎤 No speech detected');
    else showToast('🎤 Voice error: ' + e.error);
    stopVoiceInput();
  };
  r.onend = () => stopVoiceInput();
  return r;
}
function toggleVoiceInput() {
  if (isRecording) return stopVoiceInput();
  if (!voiceRecognition) voiceRecognition = initVoiceRecognition();
  if (!voiceRecognition) { showToast('⚠️ Voice not supported. Use Chrome/Edge.'); return; }
  voiceRecognition.lang = document.getElementById('voice-lang').value || 'en-IN';
  try { voiceRecognition.start(); } catch (e) {}
}
function stopVoiceInput() {
  isRecording = false;
  document.getElementById('btn-voice')?.classList.remove('recording');
  const i = document.getElementById('mic-icon'); if (i) i.textContent = '🎤';
  showListeningToast(false);
  try { voiceRecognition?.stop(); } catch (e) {}
}
function showListeningToast(show) {
  let t = document.getElementById('voice-listening-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'voice-listening-toast';
    t.className = 'voice-listening-toast';
    const w = document.querySelector('.magic-input-wrap');
    if (w) w.appendChild(t);
  }
  t.innerHTML = '<span>Listening… speak now</span>';
  t.classList.toggle('show', show);
}

/* ══════════════════════════════════════════════════
   TANGLISH
═════════════════════════════════════════════════ */
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
  [/\bmela|mele\b/i, 'at the top'],
  [/\bvalathu\b/i, 'on the right'],
  [/\bidathu\b/i, 'on the left']
];
function enrichTanglishForAI(text) {
  let out = text;
  TANGLISH_HINTS.forEach(([rx, en]) => { if (rx.test(text)) out += ` [hint: ${en}]`; });
  return out;
}

/* ══════════════════════════════════════════════════
   REFINE
═════════════════════════════════════════════════ */
async function executeRefine() {
  const input = document.getElementById('refine-query');
  const query = input.value.trim();
  if (!query) return;
  const c = generatedDesigns[activeDesignIndex];
  if (!c) return;
  const isAdmin = currentViewMode === 'admin';
  const snapHtml = isAdmin ? c.adminHtml : currentHtml;
  const cName = c.name || `Variation ${activeDesignIndex + 1}`;

  appendUserChatMessage(query);
  const enriched = enrichTanglishForAI(query);
  input.value = '';
  const btn = document.getElementById('btn-refine');
  btn.disabled = true; btn.innerHTML = '⏳';

  const log = document.getElementById('ai-chat-log');
  let typing = document.createElement('div');
  typing.className = 'msg ai';
  typing.id = 'ai-typing-indicator';
  typing.innerHTML = '<div class="msg-avatar">✦</div><div class="msg-body"><div class="msg-bubble"><span class="typing-dots"><i></i><i></i><i></i></span></div></div>';
  log.appendChild(typing); log.scrollTop = log.scrollHeight;

  try {
    if (!window.PuterService?.chatAndEdit) throw new Error('PuterService not available');
    const res = await window.PuterService.chatAndEdit({
      userPrompt: enriched, selectedElement: null, currentHtml: snapHtml,
      context: {
        bizName: document.getElementById('biz_name')?.value || 'Website',
        summary: `${cName}${isAdmin ? ' (Admin Panel)' : ''}`
      }
    });
    document.getElementById('ai-typing-indicator')?.remove();
    appendGeminiChatMessage(formatMarkdown(res.conversation));
    if (res.isEdit && res.updatedHtml) {
      if (isAdmin) {
        c.adminHtml = res.updatedHtml;
        updateLiveIframe(stabilizeAdminHtml(c.adminHtml));
        showToast('✨ Admin panel updated!');
      } else {
        pushHistory(activeDesignIndex, snapHtml);
        let n = res.updatedHtml;
        if (n.includes('<html') || n.includes('<!DOCTYPE')) currentHtml = n;
        else currentHtml = currentHtml.replace('</body>', `${n}\n</body>`);
        c.html = currentHtml;
        updateLiveIframe(currentHtml);
        updateUndoBtn();
        showToast('✨ Site updated!');
      }
      saveSessionNow();
      const pb = document.getElementById('preview-container');
      if (pb) { pb.style.transition = 'box-shadow .3s ease'; pb.style.boxShadow = '0 0 35px rgba(99,102,241,.65)'; setTimeout(() => pb.style.boxShadow = '', 1200); }
    }
  } catch (err) {
    document.getElementById('ai-typing-indicator')?.remove();
    console.warn('[Refine error]:', err);
    if (window.WebsiteGenerator?.isQuotaOrCreditError?.(err)) {
      appendGeminiChatMessage('⚠️ Puter credit limit. Switch account to continue.');
    } else {
      appendGeminiChatMessage(`⚠️ AI request failed: ${escapeHtml(err.message)}`);
    }
  } finally {
    btn.disabled = false; btn.innerHTML = '➤';
  }
}
document.getElementById('refine-query')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); executeRefine(); }
});

/* ══════════════════════════════════════════════════
   STUDIO HANDOFF
═════════════════════════════════════════════════ */
function openStudioInNewTab() {
  const c = generatedDesigns[activeDesignIndex];
  if (!c) return;
  saveSessionNow();
  window.open('<?= SITE_URL ?>/studio.php?concept=' + activeDesignIndex, '_blank');
  showToast('🎨 Studio opened');
}
window.addEventListener('focus', () => {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return;
    const p = JSON.parse(raw);
    if (p?.designs?.[activeDesignIndex]) {
      const saved = p.designs[activeDesignIndex].html;
      if (saved && saved !== currentHtml && currentViewMode === 'site') {
        pushHistory(activeDesignIndex, currentHtml);
        currentHtml = saved;
        generatedDesigns = p.designs;
        updateLiveIframe(currentHtml);
        updateUndoBtn();
        showToast('🔄 Synced with Studio!');
      }
    }
  } catch (e) {}
});

/* ══════════════════════════════════════════════════
   ★ PUBLISH — REDIRECT TO publish.php
═════════════════════════════════════════════════ */
function saveAndProceedToPayment() {
  // Save everything to localStorage first
  saveSessionNow();

  const c = generatedDesigns[activeDesignIndex];
  if (!c || !c.html) {
    showToast('⚠️ No design found. Please generate first.');
    return;
  }

  // Extra safety — persist mode + requirements
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (raw) {
      const p = JSON.parse(raw);
      p.selectedGenMode = selectedGenMode;
      p.activeDesignIndex = activeDesignIndex;
      p.bizName = document.getElementById('biz_name')?.value || 'My Website';
      p.wizard = p.wizard || {};
      p.wizard.admin_requirements = document.getElementById('admin_requirements')?.value.trim() || p.wizard.admin_requirements || '';
      localStorage.setItem(SESSION_KEY, JSON.stringify(p));
    }
  } catch (e) {}

  showToast('✓ Design saved! Opening publish wizard…');

  setTimeout(() => {
    window.location.href = '<?= SITE_URL ?>/publish.php';
  }, 550);
}

/* ══════════════════════════════════════════════════
   TOAST
═════════════════════════════════════════════════ */
let toastTimeout;
function showToast(text) {
  const t = document.getElementById('toast');
  const tt = document.getElementById('toast-text');
  if (!t || !tt) return;
  tt.textContent = text;
  t.classList.add('show');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => t.classList.remove('show'), 3200);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>