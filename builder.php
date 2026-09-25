<?php
require_once __DIR__ . '/config.php';
$page_title = 'AI Website Builder — Multi-Design & Interactive AI Co-Pilot';
$page_desc  = 'Generate 3 bespoke website concepts, interact with the AI Co-Pilot to update details, edit live visually, and export clean production code.';
require_once __DIR__ . '/includes/nav.php';
?>
<style>
/* ══ BUILDER & MULTI-DESIGN STUDIO ══ */
body { background:#0a0d14; color:#e2e8f0; font-family:'Inter',system-ui,sans-serif; }

/* ── Wizard Screen ── */
#wizard-screen {
  min-height: calc(100vh - 64px);
  background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #0a0d14 70%);
  display: flex; align-items: center; justify-content: center;
  padding: 3rem 1.25rem;
}
.wizard-card {
  background: #111622; border: 1px solid #1e293b; border-radius: 24px;
  width: 100%; max-width: 720px;
  box-shadow: 0 25px 60px -15px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.05);
  overflow: hidden;
}
.wizard-header {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  padding: 2.25rem 2.5rem; color: #fff;
}
.wizard-header h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.4rem; letter-spacing: -0.02em; }
.wizard-header p  { color: rgba(255,255,255,0.85); font-size: 0.95rem; }
.wizard-progress {
  display: flex; gap: 0.5rem; padding: 1.25rem 2.5rem;
  background: #0d121c; border-bottom: 1px solid #1e293b;
}
.wp-dot { flex: 1; height: 5px; border-radius: 999px; background: #1e293b; transition: all 0.3s; }
.wp-dot.done { background: #10b981; }
.wp-dot.active { background: linear-gradient(90deg, #6366f1, #a855f7); box-shadow: 0 0 10px rgba(99,102,241,0.5); }

.wizard-body { padding: 2.25rem 2.5rem; }
.wizard-step { display: none; }
.wizard-step.active { display: block; animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

.wiz-section-label {
  font-size: 0.78rem; font-weight: 700; color: #818cf8; letter-spacing: 0.1em;
  text-transform: uppercase; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;
}
.w-group { margin-bottom: 1.4rem; }
.w-label { display: block; font-size: 0.88rem; font-weight: 600; color: #f1f5f9; margin-bottom: 0.45rem; }
.w-label .req { color: #f43f5e; margin-left: 0.2rem; }
.w-hint { font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem; line-height: 1.4; }
.w-input, .w-select, .w-textarea {
  width: 100%; padding: 0.75rem 1rem;
  border: 1.5px solid #283347; border-radius: 10px;
  font-family: inherit; font-size: 0.92rem; color: #f8fafc;
  background: #0b0f17; transition: border-color 0.2s, box-shadow 0.2s;
  -webkit-appearance: none;
}
.w-input:focus, .w-select:focus, .w-textarea:focus {
  outline: none; border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.25); background: #111726;
}
.w-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.1rem;
  padding-right: 2.5rem;
}
.w-select option { background: #111622; color: #f8fafc; }
.w-textarea { min-height: 95px; resize: vertical; }
.w-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 520px) { .w-row { grid-template-columns: 1fr; } }

.style-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
@media (max-width: 480px) { .style-grid { grid-template-columns: repeat(2, 1fr); } }
.style-card { position: relative; }
.style-card input { position: absolute; opacity: 0; width: 0; height: 0; }
.style-card label {
  display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
  padding: 0.85rem 0.5rem; border: 1.5px solid #283347; border-radius: 12px;
  background: #0b0f17; cursor: pointer; text-align: center;
  font-size: 0.8rem; font-weight: 600; color: #94a3b8; transition: all 0.2s;
}
.style-card input:checked + label {
  border-color: #6366f1; background: #1e1b4b; color: #a5b4fc; box-shadow: 0 0 15px rgba(99,102,241,0.25);
}
.style-card label:hover { border-color: #6366f1; }

.color-grid { display: flex; flex-wrap: wrap; gap: 0.65rem; }
.color-swatch { position: relative; }
.color-swatch input { position: absolute; opacity: 0; }
.color-swatch label {
  display: block; width: 42px; height: 42px; border-radius: 50%;
  cursor: pointer; border: 3px solid transparent; transition: transform 0.2s, border-color 0.2s;
}
.color-swatch input:checked + label { border-color: #fff; transform: scale(1.15); box-shadow: 0 0 12px rgba(255,255,255,0.4); }
.color-swatch label:hover { transform: scale(1.1); }

.check-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
@media (max-width: 480px) { .check-grid { grid-template-columns: 1fr; } }
.wcheck {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.6rem 0.85rem; border: 1.5px solid #283347; border-radius: 10px;
  background: #0b0f17; cursor: pointer; transition: all 0.2s;
}
.wcheck:hover { border-color: #6366f1; }
.wcheck input { accent-color: #6366f1; width: 17px; height: 17px; }
.wcheck span { font-size: 0.85rem; color: #cbd5e1; }
.wcheck:has(input:checked) { border-color: #6366f1; background: #181938; }

.wizard-footer {
  padding: 1.5rem 2.5rem; background: #0d121c; border-top: 1px solid #1e293b;
  display: flex; justify-content: space-between; align-items: center;
}
.wbtn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1.5rem; border-radius: 10px;
  font-family: inherit; font-size: 0.9rem; font-weight: 600;
  border: none; cursor: pointer; transition: all 0.2s;
}
.wbtn-ghost { background: transparent; color: #94a3b8; border: 1.5px solid #283347; }
.wbtn-ghost:hover { color: #fff; border-color: #64748b; }
.wbtn-primary {
  background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff;
  box-shadow: 0 4px 15px rgba(79,70,229,0.4);
}
.wbtn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.5); }
.wbtn-generate {
  background: linear-gradient(135deg, #10b981, #059669); color: #fff;
  font-size: 0.95rem; font-weight: 700; padding: 0.85rem 2rem;
  box-shadow: 0 4px 20px rgba(16,185,129,0.4);
}
.wbtn-generate:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(16,185,129,0.55); }

/* ══ DESIGNS SELECTION SHOWCASE ══ */
#designs-screen {
  display: none;
  min-height: calc(100vh - 64px);
  padding: 3rem 1.5rem 5rem;
  background: radial-gradient(circle at 50% 10%, #1e1b4b 0%, #0a0d14 60%);
}
.designs-header { text-align: center; max-width: 780px; margin: 0 auto 3rem; }
.designs-badge {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);
  color: #a5b4fc; padding: 0.35rem 0.9rem; border-radius: 999px;
  font-size: 0.8rem; font-weight: 700; margin-bottom: 1rem;
}
.designs-header h1 {
  font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: #fff;
  letter-spacing: -0.02em; margin-bottom: 0.75rem;
}
.designs-header p { font-size: 1.05rem; color: #94a3b8; line-height: 1.6; }

.designs-grid {
  max-width: 1300px; margin: 0 auto;
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;
}
@media (max-width: 990px) { .designs-grid { grid-template-columns: 1fr; max-width: 650px; } }

.design-card {
  background: #111622; border: 1.5px solid #283347; border-radius: 20px;
  overflow: hidden; display: flex; flex-direction: column;
  box-shadow: 0 15px 40px rgba(0,0,0,0.4);
  transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
  position: relative;
}
.design-card:hover {
  transform: translateY(-6px); border-color: #6366f1;
  box-shadow: 0 20px 50px rgba(99,102,241,0.25);
}
.design-badge-top {
  position: absolute; top: 1rem; left: 1rem; z-index: 10;
  background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.15);
  color: #38bdf8; font-size: 0.75rem; font-weight: 700;
  padding: 0.3rem 0.75rem; border-radius: 999px;
}
.design-preview-box {
  height: 300px; background: #0b0f17; position: relative; overflow: hidden;
  border-bottom: 1px solid #1e293b;
}
.design-preview-iframe {
  width: 1400px; height: 900px; border: none;
  transform: scale(0.33); transform-origin: top left; pointer-events: none;
}
.design-preview-box::after {
  content: '👁 Click "Full Preview" to see it live';
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 1.2rem 1rem 0.6rem;
  background: linear-gradient(to top, rgba(11,15,23,0.95), transparent);
  color: #94a3b8; font-size: 0.75rem; font-weight: 600;
  text-align: center; pointer-events: none;
}
.design-content { padding: 1.75rem; display: flex; flex-direction: column; flex: 1; }
.design-content h3 { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
.design-content p { font-size: 0.88rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; flex: 1; }

.design-actions { display: grid; grid-template-columns: 1fr 1.4fr; gap: 0.75rem; }
.btn-preview-modal {
  padding: 0.75rem; border-radius: 10px; border: 1.5px solid #283347;
  background: #0b0f17; color: #cbd5e1; font-weight: 600; font-size: 0.85rem;
  cursor: pointer; text-align: center; transition: all 0.2s;
}
.btn-preview-modal:hover { border-color: #6366f1; color: #fff; }
.btn-choose-design {
  padding: 0.75rem; border-radius: 10px; border: none;
  background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff;
  font-weight: 700; font-size: 0.85rem; cursor: pointer;
  box-shadow: 0 4px 15px rgba(79,70,229,0.35); transition: all 0.2s;
}
.btn-choose-design:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.5); }

/* ══ BUILDER WORKSPACE ══ */
#builder-screen {
  display: none; height: calc(100vh - 64px); flex-direction: column;
  background: #090d14;
}

#mobile-builder-warning {
  display: none; flex-direction: column; align-items: center; justify-content: center;
  padding: 3rem 1.5rem; text-align: center; min-height: calc(100vh - 64px);
  background: radial-gradient(circle at 50% 30%, #1e1b4b 0%, #0a0d14 70%);
}
#mobile-builder-warning .mb-icon { font-size: 3rem; margin-bottom: 1rem; }
#mobile-builder-warning h2 { color: #fff; font-size: 1.4rem; font-weight: 800; margin-bottom: 0.75rem; }
#mobile-builder-warning p { color: #94a3b8; font-size: 0.95rem; max-width: 380px; line-height: 1.6; }
@media (max-width: 768px) {
  #builder-screen .builder-toolbar,
  #builder-screen .builder-main,
  #builder-screen .refine-dock { display: none !important; }
  #mobile-builder-warning { display: flex; }
}

.builder-toolbar {
  min-height: 56px; background: #101522; border-bottom: 1px solid #1e293b;
  display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem;
  flex-shrink: 0; flex-wrap: wrap;
}
.tb-title { font-weight: 800; font-size: 0.9rem; color: #818cf8; display: flex; align-items: center; gap: 0.4rem; white-space: nowrap; }
.tb-site-badge {
  background: #1e1b4b; border: 1px solid rgba(99,102,241,0.3);
  color: #c7d2fe; font-size: 0.75rem; font-weight: 700;
  padding: 0.2rem 0.6rem; border-radius: 6px;
  max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.tb-divider { width: 1px; height: 24px; background: #1e293b; }

.design-tabs { display: flex; gap: 0.35rem; }
.d-tab {
  padding: 0.35rem 0.75rem; border-radius: 7px; border: 1px solid #283347;
  background: #0b0f17; color: #94a3b8; font-size: 0.75rem; font-weight: 700;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.d-tab.active { background: #4f46e5; border-color: #6366f1; color: #fff; box-shadow: 0 0 10px rgba(99,102,241,0.4); }
.d-tab:hover:not(.active) { border-color: #64748b; color: #cbd5e1; }

.tb-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.42rem 0.8rem; border-radius: 7px;
  font-family: inherit; font-size: 0.78rem; font-weight: 600;
  border: 1px solid #283347; background: #0b0f17; color: #cbd5e1;
  cursor: pointer; transition: all 0.15s; white-space: nowrap;
}
.tb-btn:hover { background: #1e293b; border-color: #64748b; }
.tb-btn.active { border-color: #6366f1; color: #a5b4fc; background: #181938; }
.tb-btn.visual-btn {
  background: linear-gradient(135deg, #4f46e5, #7c3aed); border: 1px solid #818cf8;
  color: #fff; font-weight: 700; box-shadow: 0 2px 10px rgba(99,102,241,0.35);
}
.tb-btn.visual-btn:hover {
  box-shadow: 0 4px 15px rgba(99,102,241,0.5); transform: translateY(-1px);
}
.tb-btn.undo-btn {
  background: #2d1b4e; color: #e9d5ff; border-color: #6b21a8;
}
.tb-btn.undo-btn:hover { background: #3f2668; border-color: #a855f7; }

.tb-menu-wrap { position: relative; }
.tb-menu {
  display: none; position: absolute; top: calc(100% + 6px); right: 0;
  background: #111622; border: 1.5px solid #283347; border-radius: 12px;
  min-width: 240px; padding: 0.4rem; z-index: 200;
  box-shadow: 0 15px 40px rgba(0,0,0,0.7);
}
.tb-menu.open { display: block; animation: menuPop 0.15s ease; }
@keyframes menuPop { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform: translateY(0); } }
.tb-menu button {
  display: flex; align-items: center; gap: 0.55rem;
  width: 100%; padding: 0.55rem 0.75rem;
  background: transparent; border: none; border-radius: 8px;
  color: #cbd5e1; font-family: inherit; font-size: 0.82rem; font-weight: 500;
  cursor: pointer; text-align: left; transition: all 0.12s;
}
.tb-menu button:hover { background: #1e293b; color: #fff; }
.tb-menu-sep { height: 1px; background: #1e293b; margin: 0.4rem 0.2rem; }
.tb-menu-label {
  font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase;
  letter-spacing: 0.08em; padding: 0.5rem 0.75rem 0.25rem;
}
.tb-menu .quick-palettes { padding: 0.25rem 0.75rem 0.5rem; flex-wrap: wrap; }

.quick-palettes { display: flex; align-items: center; gap: 0.35rem; }
.qp-dot {
  width: 20px; height: 20px; border-radius: 50%; cursor: pointer;
  border: 1.5px solid transparent; transition: transform 0.15s;
}
.qp-dot:hover { transform: scale(1.2); border-color: #fff; }

.tb-btn.next-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 1px solid #34d399; color: #fff; font-weight: 800;
  box-shadow: 0 2px 12px rgba(16,185,129,0.45);
  animation: nextPulse 2.4s ease-in-out 3;
}
@keyframes nextPulse {
  0%,100% { box-shadow: 0 2px 12px rgba(16,185,129,0.45); }
  50%     { box-shadow: 0 2px 20px rgba(16,185,129,0.75); }
}
.tb-btn.next-btn:hover { transform: translateY(-1px); }

.publish-status {
  display:none; align-items:center; gap:0.4rem;
  font-size:0.74rem; font-weight:700;
  padding:0.25rem 0.7rem; border-radius:999px;
  border:1px solid rgba(16,185,129,0.4);
  background:rgba(16,185,129,0.12); color:#34d399;
}
.publish-status.live a { color:#6ee7b7; text-decoration:underline; }

.builder-main { display: flex; flex: 1; overflow: hidden; position: relative; }

.preview-pane { flex: 1; display: flex; flex-direction: column; background: #0d121c; width: 100%; height: 100%; }
.preview-bar {
  background: #0d121c; border-bottom: 1px solid #1e293b;
  padding: 0 1rem; height: 42px; display: flex; align-items: center; justify-content: space-between;
}
.device-toggles { display: flex; gap: 0.3rem; }
.device-btn {
  padding: 0.28rem 0.6rem; border: 1px solid #283347; border-radius: 6px;
  background: transparent; color: #94a3b8; cursor: pointer; font-size: 0.75rem;
  font-family: inherit;
}
.device-btn.active { color: #fff; border-color: #6366f1; background: #1e1b4b; }

.preview-container {
  flex: 1; display: flex; justify-content: center; background: #06090e;
  overflow: hidden; position: relative;
}
#live-iframe {
  width: 100%; height: 100%; border: none; background: #fff;
  transition: width 0.3s ease;
}
.preview-container.tablet #live-iframe { width: 768px; }
.preview-container.mobile #live-iframe { width: 390px; }

.refine-dock {
  background: #0a0e1a;
  border-top: 1px solid rgba(99,102,241,0.2);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  box-shadow: 0 -20px 60px rgba(0,0,0,0.5);
  position: relative;
}
.refine-dock::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(99,102,241,0.6), rgba(168,85,247,0.6), transparent);
}

.refine-header {
  padding: 0.75rem 1.25rem;
  background: linear-gradient(180deg, #0d1220 0%, #0a0e1a 100%);
  border-bottom: 1px solid rgba(30,41,59,0.8);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.gemini-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}
.gemini-avatar {
  width: 38px; height: 38px;
  border-radius: 12px;
  background: linear-gradient(135deg, #4285F4 0%, #9B72CF 50%, #D96570 100%);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 1.15rem; font-weight: 800;
  box-shadow: 0 4px 14px rgba(155,114,207,0.35), inset 0 1px 0 rgba(255,255,255,0.2);
  position: relative;
  flex-shrink: 0;
}
.gemini-avatar::after {
  content: '';
  position: absolute;
  bottom: -2px; right: -2px;
  width: 12px; height: 12px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #0a0e1a;
  animation: statusPulse 2s ease-in-out infinite;
}
@keyframes statusPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.6); }
  50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0); }
}
.gemini-meta { display: flex; flex-direction: column; min-width: 0; }
.gemini-title {
  font-size: 0.86rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.01em;
  display: flex; align-items: center; gap: 0.4rem;
  line-height: 1.2;
}
.gemini-title .pro-badge {
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  background: linear-gradient(135deg, rgba(66,133,244,0.2), rgba(155,114,207,0.2));
  border: 1px solid rgba(155,114,207,0.4);
  color: #c7d2fe;
  text-transform: uppercase;
}
.gemini-subtitle {
  font-size: 0.68rem;
  color: #94a3b8;
  font-weight: 500;
  display: flex; align-items: center; gap: 0.35rem;
  margin-top: 0.1rem;
}
.gemini-subtitle .dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px rgba(16,185,129,0.8);
}
.refine-header-actions { display: flex; align-items: center; gap: 0.4rem; }
.header-icon-btn {
  width: 32px; height: 32px;
  border-radius: 8px;
  background: #111726;
  border: 1px solid #1e293b;
  color: #94a3b8;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem;
  transition: all 0.15s;
  font-family: inherit;
}
.header-icon-btn:hover {
  background: #1e293b;
  color: #fff;
  border-color: #475569;
}

.ai-chat-log {
  max-height: 300px;
  overflow-y: auto;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  font-size: 0.84rem;
  scroll-behavior: smooth;
  background:
    radial-gradient(ellipse at top left, rgba(99,102,241,0.05) 0%, transparent 60%),
    radial-gradient(ellipse at bottom right, rgba(168,85,247,0.04) 0%, transparent 60%);
}
.ai-chat-log::-webkit-scrollbar { width: 6px; }
.ai-chat-log::-webkit-scrollbar-track { background: transparent; }
.ai-chat-log::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #475569, #334155);
  border-radius: 10px;
}
.ai-chat-log::-webkit-scrollbar-thumb:hover { background: #64748b; }

.chat-row {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  animation: chatIn 0.35s cubic-bezier(0.22,1,0.36,1);
}
.chat-row.user-row { flex-direction: row-reverse; }
@keyframes chatIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.chat-avatar {
  width: 32px; height: 32px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; font-weight: 800;
  flex-shrink: 0;
  position: relative;
}
.chat-avatar.gemini {
  background: linear-gradient(135deg, #4285F4 0%, #9B72CF 50%, #D96570 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(155,114,207,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
}
.chat-avatar.user {
  background: linear-gradient(135deg, #334155, #1e293b);
  color: #cbd5e1;
  font-size: 0.7rem;
  border: 1px solid #475569;
}
.chat-bubble-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-width: 82%;
  min-width: 0;
}
.chat-row.user-row .chat-bubble-wrap { align-items: flex-end; }
.chat-msg {
  padding: 0.7rem 1rem;
  border-radius: 14px;
  line-height: 1.55;
  font-size: 0.84rem;
  word-wrap: break-word;
  position: relative;
  transition: all 0.15s;
}
.chat-msg.ai {
  background: linear-gradient(180deg, #131929 0%, #0f1524 100%);
  border: 1px solid rgba(99,102,241,0.2);
  color: #e2e8f0;
  border-top-left-radius: 4px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.chat-msg.user {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: #fff;
  border-top-right-radius: 4px;
  box-shadow: 0 2px 12px rgba(99,102,241,0.3);
}
.chat-msg strong { font-weight: 700; color: inherit; }
.chat-msg a { color: #6ee7b7; text-decoration: none; border-bottom: 1px dotted currentColor; }
.chat-msg a:hover { border-bottom-style: solid; }
.chat-timestamp {
  font-size: 0.65rem;
  color: #64748b;
  font-weight: 500;
  padding: 0 0.35rem;
  user-select: none;
}
.chat-actions {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
  margin-top: 0.1rem;
}
.chat-row:hover .chat-actions { opacity: 1; }
.chat-action-btn {
  font-size: 0.65rem;
  padding: 0.15rem 0.45rem;
  border-radius: 5px;
  background: rgba(148,163,184,0.1);
  border: 1px solid rgba(148,163,184,0.2);
  color: #94a3b8;
  cursor: pointer;
  font-family: inherit;
  font-weight: 600;
  transition: all 0.15s;
}
.chat-action-btn:hover {
  background: rgba(99,102,241,0.15);
  border-color: rgba(99,102,241,0.4);
  color: #c7d2fe;
}
.chat-welcome {
  background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(168,85,247,0.06) 100%);
  border: 1px solid rgba(99,102,241,0.25);
  border-radius: 14px;
  padding: 1rem 1.1rem;
  display: flex; flex-direction: column; gap: 0.6rem;
}
.chat-welcome-title {
  font-size: 0.88rem; font-weight: 800; color: #fff;
  display: flex; align-items: center; gap: 0.5rem;
}
.chat-welcome-desc {
  font-size: 0.78rem; color: #94a3b8; line-height: 1.55;
}

.chat-thinking {
  display: none;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 0.9rem;
  background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(168,85,247,0.08));
  border: 1px dashed rgba(99,102,241,0.4);
  border-radius: 12px;
  color: #c7d2fe;
  font-size: 0.78rem;
  font-weight: 600;
  align-self: flex-start;
  margin: 0 1.25rem 0.75rem;
}
.chat-thinking .shimmer-text {
  background: linear-gradient(90deg, #c7d2fe 0%, #a5b4fc 25%, #e9d5ff 50%, #a5b4fc 75%, #c7d2fe 100%);
  background-size: 200% 100%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: shimmer 2s linear infinite;
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.thinking-dots { display: flex; gap: 4px; }
.thinking-dots span {
  width: 6px; height: 6px; background: #818cf8; border-radius: 50%;
  animation: bounceDot 1.4s infinite ease-in-out both;
}
.thinking-dots span:nth-child(1) { animation-delay: -0.32s; }
.thinking-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes bounceDot {
  0%, 80%, 100% { transform: scale(0.5); opacity: 0.4; }
  40%           { transform: scale(1); opacity: 1; }
}

.refine-categories {
  display: flex; gap: 0.45rem;
  overflow-x: auto;
  padding: 0.5rem 1.25rem 0.3rem;
  scrollbar-width: thin;
}
.refine-categories::-webkit-scrollbar { height: 4px; }
.refine-categories::-webkit-scrollbar-thumb { background: #283347; border-radius: 4px; }
.refine-chip {
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  border: 1px solid #283347;
  background: linear-gradient(180deg, #111726, #0b0f17);
  color: #94a3b8;
  font-size: 0.74rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.18s;
  display: inline-flex; align-items: center; gap: 0.35rem;
  font-family: inherit;
}
.refine-chip:hover {
  border-color: rgba(99,102,241,0.6);
  color: #e0e7ff;
  background: linear-gradient(180deg, #1e1b4b, #181938);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99,102,241,0.2);
}

.refine-input-row {
  display: flex;
  gap: 0.6rem;
  padding: 0.65rem 1.25rem 0.85rem;
  align-items: center;
}
.refine-input-wrap {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  background: #080c14;
  border: 1.5px solid #283347;
  border-radius: 12px;
  transition: all 0.2s;
}
.refine-input-wrap:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.15), 0 0 20px rgba(99,102,241,0.1);
  background: #0b0f1c;
}
.refine-input-icon {
  padding: 0 0.7rem;
  color: #6366f1;
  font-size: 0.95rem;
  flex-shrink: 0;
  opacity: 0.7;
}
.refine-input-wrap:focus-within .refine-input-icon { opacity: 1; }
.refine-input {
  flex: 1;
  padding: 0.75rem 0.85rem 0.75rem 0;
  background: transparent;
  border: none;
  color: #fff;
  font-family: inherit;
  font-size: 0.86rem;
  outline: none;
  min-width: 0;
}
.refine-input::placeholder { color: #64748b; }
.refine-btn {
  padding: 0.75rem 1.4rem;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #4285F4 0%, #9B72CF 50%, #D96570 100%);
  background-size: 200% 100%;
  color: #fff;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(155,114,207,0.4), inset 0 1px 0 rgba(255,255,255,0.15);
  transition: all 0.25s;
  font-family: inherit;
  display: inline-flex; align-items: center; gap: 0.4rem;
}
.refine-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  background-position: 100% 0;
  box-shadow: 0 6px 20px rgba(155,114,207,0.55), inset 0 1px 0 rgba(255,255,255,0.2);
}
.refine-btn:active:not(:disabled) { transform: translateY(0); }
.refine-btn:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }

.modal-overlay {
  display: none; position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
  align-items: center; justify-content: center; padding: 2rem;
}
.modal-overlay.active { display: flex; }
.modal-box {
  background: #111622; border: 1.5px solid #283347; border-radius: 20px;
  width: 90%; max-width: 1200px; height: 85vh; display: flex; flex-direction: column;
  overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.8);
}
.modal-bar {
  padding: 1rem 1.5rem; background: #0d121c; border-bottom: 1px solid #1e293b;
  display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;
}
.modal-iframe { flex: 1; border: none; background: #fff; }

.toast {
  position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 10000;
  background: #111622; border: 1.5px solid #283347; border-radius: 12px;
  padding: 0.85rem 1.4rem; color: #fff; font-size: 0.88rem; font-weight: 600;
  box-shadow: 0 10px 30px rgba(0,0,0,0.6); transform: translateY(100px); opacity: 0;
  transition: all 0.3s ease; display: flex; align-items: center; gap: 0.6rem;
  max-width: 90vw;
}
.toast.show { transform: translateY(0); opacity: 1; }

.plan-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:1rem; margin-bottom:1.5rem; }
@media (max-width: 640px){ .plan-grid { grid-template-columns:1fr; } }
.plan-card {
  border:1.5px solid #283347; border-radius:16px; padding:1.25rem 1rem;
  background:#0b0f17; cursor:pointer; text-align:center;
  transition: all 0.2s; position:relative;
}
.plan-card:hover { border-color:#6366f1; }
.plan-card.selected { border-color:#10b981; background:#062b22; box-shadow:0 0 22px rgba(16,185,129,0.25); }
.plan-card .plan-name { font-size:0.82rem; font-weight:800; color:#a5b4fc; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.5rem; }
.plan-card .plan-price { font-size:1.9rem; font-weight:900; color:#fff; letter-spacing:-0.02em; }
.plan-card .plan-price small { font-size:0.8rem; font-weight:600; color:#94a3b8; }
.plan-card .plan-features { font-size:0.78rem; color:#94a3b8; margin-top:0.75rem; line-height:1.7; text-align:left; }
.plan-card .plan-tag {
  position:absolute; top:-10px; left:50%; transform:translateX(-50%);
  background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff;
  font-size:0.65rem; font-weight:800; padding:0.15rem 0.6rem;
  border-radius:999px; letter-spacing:0.05em; text-transform:uppercase;
}
.pay-summary {
  background:#090d16; border:1px solid #1e293b; border-radius:12px;
  padding:1rem 1.25rem; margin-bottom:1.25rem; font-size:0.85rem; color:#cbd5e1;
}
.pay-summary .row { display:flex; justify-content:space-between; padding:0.3rem 0; }
.pay-summary .row.total {
  border-top:1px solid #1e293b; margin-top:0.4rem; padding-top:0.7rem;
  font-weight:800; color:#fff; font-size:1.05rem;
}
.pay-methods { display:flex; gap:0.6rem; flex-wrap:wrap; margin-bottom:1rem; }
.pay-method {
  flex:1; min-width:120px; display:flex; align-items:center; justify-content:center;
  gap:0.4rem; padding:0.7rem; border:1.5px solid #283347; border-radius:10px;
  background:#0b0f17; cursor:pointer; font-size:0.82rem; font-weight:700;
  color:#cbd5e1; transition:all 0.15s;
}
.pay-method:hover { border-color:#6366f1; }
.pay-method.selected { border-color:#10b981; background:#062b22; color:#6ee7b7; }

.pay-success-anim { text-align:center; padding:1.5rem 1rem; }
.pay-success-anim .check {
  width:74px; height:74px; border-radius:50%; margin:0 auto 1rem;
  background:linear-gradient(135deg,#10b981,#059669); color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-size:2.2rem; font-weight:900;
  box-shadow:0 0 40px rgba(16,185,129,0.55);
  animation: popCheck 0.5s cubic-bezier(.2,1.4,.4,1);
}
@keyframes popCheck { 0%{ transform:scale(0); } 100%{ transform:scale(1); } }
.live-url-box {
  display:flex; align-items:center; gap:0.5rem;
  background:#080c14; border:1.5px solid #10b981; border-radius:10px;
  padding:0.85rem 1rem; font-size:0.85rem; color:#6ee7b7; margin-top:0.5rem;
  font-family:'Fira Code', monospace; word-break:break-all;
}
.paypal-badge {
  display:inline-flex; align-items:center; gap:0.4rem;
  background:rgba(0,119,200,0.12); border:1px solid rgba(0,119,200,0.35);
  color:#60a5fa; padding:0.25rem 0.7rem; border-radius:999px;
  font-size:0.72rem; font-weight:700; margin-bottom:1rem;
}
.pay-method.paypal-primary {
  background: linear-gradient(135deg, #003087, #0070ba);
  border-color: #009cde; color: #fff; font-weight: 800;
}
.pay-method.paypal-primary:hover { border-color: #00b4ff; }
.pay-method.paypal-primary.selected {
  border-color: #00d4ff; box-shadow: 0 0 20px rgba(0,156,222,0.55);
}
</style>

<!-- ═════════════════════════════════════════════════════
     1. WIZARD SCREEN
═════════════════════════════════════════════════════ -->
<div id="wizard-screen">
  <div class="wizard-card">
    <div class="wizard-header">
      <h1>✦ AI Website Generator</h1>
      <p>Fill in your requirements. Our AI creates 3 bespoke design concepts with working JavaScript, visual editing, and an AI Co-Pilot.</p>
    </div>

    <div id="resume-project-banner" style="display:none; background:#181938; border-bottom:1px solid #3b4260; padding:0.85rem 2.5rem; justify-content:space-between; align-items:center; font-size:0.84rem; gap:0.75rem; flex-wrap:wrap;">
      <div style="color:#c7d2fe; display:flex; align-items:center; gap:0.5rem">
        <span style="font-size:1.1rem">📁</span>
        <span>Saved project found for <strong id="resume-project-name" style="color:#fff">Website</strong></span>
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem">
        <button class="tb-btn visual-btn" onclick="resumeSavedProject()" style="padding:0.28rem 0.85rem; font-size:0.75rem">⚡ Resume Editing</button>
        <button class="tb-btn" onclick="dismissSavedProject()" style="padding:0.28rem 0.55rem; font-size:0.75rem; background:transparent">✕ Dismiss</button>
      </div>
    </div>

    <div class="wizard-progress">
      <div class="wp-dot active" id="wp1"></div>
      <div class="wp-dot" id="wp2"></div>
      <div class="wp-dot" id="wp3"></div>
      <div class="wp-dot" id="wp4"></div>
    </div>

    <div class="wizard-body">
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
              <option>Creative & Digital Agency</option>
              <option>Restaurant, Cafe & Bar</option>
              <option>Tech Startup & SaaS</option>
              <option>Professional Services & Legal</option>
              <option>Healthcare & Wellness Clinic</option>
              <option>Real Estate & Architecture</option>
              <option>Fitness Center & Personal Trainer</option>
              <option>E-Commerce & Retail</option>
              <option>Personal Portfolio & Creator</option>
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
          <div class="w-hint">Helps the AI adapt tone of voice and user journey.</div>
        </div>
        <div class="w-group">
          <label class="w-label" for="biz_services">Services / Products Offered (comma-separated)</label>
          <input class="w-input" type="text" id="biz_services" value="UI/UX Design, Full-Stack Web Development, Brand Identity, Growth Optimization">
        </div>
      </div>

      <div class="wizard-step" id="step2">
        <div class="wiz-section-label">Step 2 of 4 · Design Style &amp; Brand Palette</div>
        <div class="w-group">
          <label class="w-label">Primary Design Direction</label>
          <div class="style-grid">
            <div class="style-card">
              <input type="radio" name="design_style" id="st_modern" value="modern" checked>
              <label for="st_modern"><span style="font-size:1.4rem">🎯</span>Modern &amp; Clean</label>
            </div>
            <div class="style-card">
              <input type="radio" name="design_style" id="st_bold" value="bold">
              <label for="st_bold"><span style="font-size:1.4rem">⚡</span>Bold &amp; Dynamic</label>
            </div>
            <div class="style-card">
              <input type="radio" name="design_style" id="st_dark" value="dark">
              <label for="st_dark"><span style="font-size:1.4rem">🌙</span>Luxury &amp; Dark</label>
            </div>
          </div>
          <div class="w-hint">
            <strong style="color:#a5b4fc">Concept 1</strong> will follow your chosen direction exactly.
            <strong style="color:#a5b4fc">Concepts 2 &amp; 3</strong> explore alternative styles — so you always get variety.
          </div>
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

      <div class="wizard-step" id="step3">
        <div class="wiz-section-label">Step 3 of 4 · Website Sections &amp; Interactive JS</div>
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

      <div class="wizard-step" id="step4">
        <div class="wiz-section-label">Step 4 of 4 · Contact Info &amp; Details</div>
        <div class="w-row">
          <div class="w-group">
            <label class="w-label" for="biz_phone">Phone Number</label>
            <input class="w-input" type="text" id="biz_phone" value="+1 (555) 890-1234">
          </div>
          <div class="w-group">
            <label class="w-label" for="biz_email">Contact Email</label>
            <input class="w-input" type="email" id="biz_email" value="hello@zenithstudio.com">
          </div>
        </div>
        <div class="w-group">
          <label class="w-label" for="biz_address">Headquarters / Location</label>
          <input class="w-input" type="text" id="biz_address" value="450 Innovation Parkway, San Francisco, CA">
        </div>
        <div style="background:#064e3b; border:1px solid #059669; border-radius:12px; padding:1rem; display:flex; gap:0.75rem; align-items:center;">
          <span style="font-size:1.5rem">✨</span>
          <div style="font-size:0.85rem; color:#a7f3d0">
            <strong>Full Working JavaScript:</strong> When visitors click "Contact" or fill forms, real JS handles smooth navigation and form submission.
          </div>
        </div>
      </div>
    </div>

    <div class="wizard-footer">
      <button class="wbtn wbtn-ghost" id="wiz-prev" onclick="wizNav(-1)" disabled>&larr; Back</button>
      <div id="wiz-step-counter" style="font-size:0.85rem; color:#64748b; font-weight:600">Step 1 of 4</div>
      <button class="wbtn wbtn-primary" id="wiz-next" onclick="wizNav(1)">Next Step &rarr;</button>
      <button class="wbtn wbtn-generate" id="wiz-generate" style="display:none" onclick="generate3Designs()">⚡ Generate 3 Concepts</button>
    </div>
  </div>
</div>

<!-- ═════════════════════════════════════════════════════
     2. 3 DESIGNS SHOWCASE
═════════════════════════════════════════════════════ -->
<div id="designs-screen">
  <div class="designs-header">
    <div class="designs-badge">✦ Multi-Design Engine</div>
    <h1 id="designs-title">Choose Your Design Concept</h1>
    <p id="designs-sub">We generated 3 bespoke website directions for your brand. Preview them live, compare, and select your favorite to edit in the live workspace.</p>
  </div>

  <div class="designs-grid" id="designs-grid">
    <div class="design-card">
      <div class="design-badge-top" id="d1-badge">Clean &amp; Minimal</div>
      <div class="design-preview-box"><iframe class="design-preview-iframe" id="d1-iframe"></iframe></div>
      <div class="design-content">
        <h3 id="d1-name">Concept 1 — Modern Minimal &amp; Crisp</h3>
        <p id="d1-desc">Balanced whitespace, clean typography, smooth section scroll, and crisp subtle cards.</p>
        <div class="design-actions">
          <button class="btn-preview-modal" onclick="openFullscreenModal(0)">👁️ Full Preview</button>
          <button class="btn-choose-design" onclick="selectDesignAndEdit(0)">Select &amp; Edit &rarr;</button>
        </div>
      </div>
    </div>
    <div class="design-card">
      <div class="design-badge-top" id="d2-badge">High-Impact Dynamic</div>
      <div class="design-preview-box"><iframe class="design-preview-iframe" id="d2-iframe"></iframe></div>
      <div class="design-content">
        <h3 id="d2-name">Concept 2 — Bold Dynamic &amp; High-Converting</h3>
        <p id="d2-desc">Vibrant gradients, high-contrast CTAs, animated stat boxes, and punchy layout.</p>
        <div class="design-actions">
          <button class="btn-preview-modal" onclick="openFullscreenModal(1)">👁️ Full Preview</button>
          <button class="btn-choose-design" onclick="selectDesignAndEdit(1)">Select &amp; Edit &rarr;</button>
        </div>
      </div>
    </div>
    <div class="design-card">
      <div class="design-badge-top" id="d3-badge">Executive Luxury</div>
      <div class="design-preview-box"><iframe class="design-preview-iframe" id="d3-iframe"></iframe></div>
      <div class="design-content">
        <h3 id="d3-name">Concept 3 — Executive Luxury &amp; Dark Mode</h3>
        <p id="d3-desc">Sophisticated night theme, ambient glows, frosted glassmorphism, and gold accents.</p>
        <div class="design-actions">
          <button class="btn-preview-modal" onclick="openFullscreenModal(2)">👁️ Full Preview</button>
          <button class="btn-choose-design" onclick="selectDesignAndEdit(2)">Select &amp; Edit &rarr;</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═════════════════════════════════════════════════════
     3. LIVE PREVIEW WORKSPACE
═════════════════════════════════════════════════════ -->
<div id="builder-screen">
  <div class="builder-toolbar">
    <div class="tb-title">✦ WebCraft AI</div>
    <div class="tb-site-badge" id="tb-biz-badge">Zenith Studio</div>
    <div class="tb-divider"></div>

    <div class="design-tabs">
      <button class="d-tab active" id="tab-d0" onclick="switchActiveDesign(0)">Concept 1</button>
      <button class="d-tab" id="tab-d1" onclick="switchActiveDesign(1)">Concept 2</button>
      <button class="d-tab" id="tab-d2" onclick="switchActiveDesign(2)">Concept 3</button>
    </div>

    <div class="tb-divider"></div>

    <button class="tb-btn" onclick="openInNewTab()" title="Open a clean production version in a new tab">👁 Preview</button>
    <button class="tb-btn visual-btn" onclick="openStudioInNewTab()" title="Open the full visual editor in a new tab">🎨 Edit in Studio ↗</button>
    <button class="tb-btn" onclick="openDetailsModal()" title="Update business details">📝 Details</button>

    <div style="flex:1"></div>

    <button class="tb-btn undo-btn" id="btn-undo-ai" onclick="undoLastAiChange()" style="display:none" title="Revert the last AI edit">↶ Undo AI</button>

    <div class="tb-menu-wrap">
      <button class="tb-btn" id="btn-actions-menu" onclick="toggleActionsMenu(event)">⚙ Actions ▾</button>
      <div class="tb-menu" id="tb-actions-menu">
        <button onclick="openInNewTab(); closeActionsMenu();">↗ Open in New Tab</button>
        <button onclick="openApiKeyModal(); closeActionsMenu();">🔑 Gemini API Key</button>
        <button onclick="backToDesigns(); closeActionsMenu();">← Back to 3 Designs</button>
        <div class="tb-menu-sep"></div>
        <div class="tb-menu-label">Quick Theme Colors</div>
        <div class="quick-palettes">
          <span class="qp-dot" style="background:#6366f1" onclick="applyQuickColor('#6366f1', '#a855f7')" title="Indigo Purple"></span>
          <span class="qp-dot" style="background:#2563eb" onclick="applyQuickColor('#2563eb', '#06b6d4')" title="Ocean Blue"></span>
          <span class="qp-dot" style="background:#059669" onclick="applyQuickColor('#059669', '#10b981')" title="Emerald Green"></span>
          <span class="qp-dot" style="background:#dc2626" onclick="applyQuickColor('#dc2626', '#f97316')" title="Crimson Red"></span>
          <span class="qp-dot" style="background:#d97706" onclick="applyQuickColor('#d97706', '#f59e0b')" title="Gold Amber"></span>
          <span class="qp-dot" style="background:#0f172a" onclick="applyQuickColor('#0f172a', '#334155')" title="Midnight Slate"></span>
        </div>
      </div>
    </div>

    <button class="tb-btn next-btn" id="btn-next-publish" onclick="saveAndProceedToPayment()" title="Save & continue to publish">Next → Save &amp; Publish</button>
    <div class="publish-status" id="publish-status"></div>
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
          <button class="tb-btn" onclick="refreshLivePreview()" style="padding:0.28rem 0.7rem; font-size:0.75rem;">↺ Reload Preview</button>
        </div>
      </div>
      <div class="preview-container" id="preview-container">
        <iframe id="live-iframe" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
      </div>
    </div>
  </div>

  <!-- ══ GEMINI AI CO-PILOT DOCK (PRO REDESIGN) ══ -->
  <div class="refine-dock">
    <div class="refine-header">
      <div class="gemini-brand">
        <div class="gemini-avatar">✦</div>
        <div class="gemini-meta">
          <div class="gemini-title">AI Co-Pilot <span class="pro-badge" style="background:linear-gradient(135deg,#06b6d4,#3b82f6)">Puter.js</span></div>
          <div class="gemini-subtitle"><span class="dot"></span> Online · DeepSeek V3</div>
        </div>
      </div>
      <div class="refine-header-actions" style="display:flex;align-items:center;gap:0.4rem;">
        <button class="header-icon-btn" id="builder-puter-btn" onclick="toggleBuilderPuterMenu(event)" title="Puter AI Account & Credits" style="padding:0.25rem 0.55rem;font-size:0.72rem;font-weight:700;background:#1e1b4b;border:1px solid #6366f1;color:#c7d2fe;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
          <span id="b-puter-dot" style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;"></span>
          <span id="b-puter-name">Puter AI</span>
        </button>
        <button class="header-icon-btn" onclick="clearChatLog()" title="Clear conversation">🗑</button>
        <button class="header-icon-btn" onclick="toggleChatHistory()" title="Toggle history" id="chat-toggle-btn">▾</button>
      </div>
    </div>

    <div class="ai-chat-log" id="ai-chat-log">
      <div class="chat-row">
        <div class="chat-avatar gemini">✦</div>
        <div class="chat-bubble-wrap">
          <div class="chat-welcome">
            <div class="chat-welcome-title">✦ Hello! I'm your Gemini Co-Pilot</div>
            <div class="chat-welcome-desc">
              I can refine, redesign, and enhance your website in real time.
              Try a suggestion below or type your own prompt.
            </div>
          </div>
          <div class="chat-timestamp">just now</div>
        </div>
      </div>
    </div>

    <div class="chat-thinking" id="chat-thinking">
      <div class="gemini-avatar" style="width:24px;height:24px;font-size:0.75rem;border-radius:7px">✦</div>
      <span class="shimmer-text">Gemini is analyzing your website…</span>
      <div class="thinking-dots"><span></span><span></span><span></span></div>
    </div>

    <div class="refine-categories">
      <span class="refine-chip" onclick="quickRefine('Add a modern 5-star customer testimonials section with client review cards')">⭐ Reviews</span>
      <span class="refine-chip" onclick="quickRefine('Add a modern pricing table with 3 plans')">💰 Pricing</span>
      <span class="refine-chip" onclick="quickRefine('Add an interactive FAQ accordion section with 3 questions')">❓ FAQ</span>
      <span class="refine-chip" onclick="quickRefine('Add a floating WhatsApp chat button to the bottom')">💬 WhatsApp</span>
      <span class="refine-chip" onclick="quickRefine('Add a partner client logos strip')">🏢 Client Logos</span>
      <span class="refine-chip" onclick="quickRefine('Add meet our leadership team section')">👥 Team</span>
      <span class="refine-chip" onclick="quickRefine('Switch theme to sleek dark mode')">🌙 Dark Mode</span>
      <span class="refine-chip" onclick="openDetailsModal()">📝 Update Details</span>
    </div>

    <div class="refine-input-row">
      <div class="refine-input-wrap">
        <span class="refine-input-icon">✦</span>
        <input type="text" class="refine-input" id="refine-query" placeholder="Ask Gemini to build, refine, or redesign anything…">
      </div>
      <button class="refine-btn" id="btn-refine" onclick="executeRefine()">
        <span>✦</span><span>Refine</span>
      </button>
    </div>
  </div>
</div>

<!-- Mobile warning -->
<div id="mobile-builder-warning">
  <div class="mb-icon">💻</div>
  <h2>Desktop Editing Recommended</h2>
  <p>The visual builder works best on a larger screen. Please open this page on a laptop or desktop to edit your website. Your project is safely saved — you can resume anytime.</p>
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="details-modal">
  <div class="modal-box" style="max-width:620px; height:auto; padding:2rem; max-height:90vh; overflow-y:auto">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem">
      <h2 style="font-size:1.3rem; color:#fff; display:flex; align-items:center; gap:0.5rem"><span>📝</span> Update Website Details</h2>
      <button class="wbtn wbtn-ghost" onclick="closeDetailsModal()" style="padding:0.35rem 0.75rem">✕</button>
    </div>
    <p style="font-size:0.85rem; color:#94a3b8; margin-bottom:1.5rem">
      Edit your business details below. The AI will sync them across your entire website in real time.
    </p>
    <div class="w-group">
      <label class="w-label" for="det-tagline">Main Headline / Tagline</label>
      <input type="text" class="w-input" id="det-tagline" placeholder="Headline displayed in Hero">
    </div>
    <div class="w-row">
      <div class="w-group">
        <label class="w-label" for="det-phone">Phone Number</label>
        <input type="text" class="w-input" id="det-phone" placeholder="+1 (555) 000-0000">
      </div>
      <div class="w-group">
        <label class="w-label" for="det-email">Email Address</label>
        <input type="email" class="w-input" id="det-email" placeholder="contact@example.com">
      </div>
    </div>
    <div class="w-group">
      <label class="w-label" for="det-address">Location / Address</label>
      <input type="text" class="w-input" id="det-address" placeholder="City, State, Country">
    </div>
    <div class="w-row">
      <div class="w-group">
        <label class="w-label" for="det-cta">Primary CTA Button Text</label>
        <input type="text" class="w-input" id="det-cta" placeholder="e.g. Schedule a Call">
      </div>
      <div class="w-group">
        <label class="w-label" for="det-service">Add New Service Card</label>
        <input type="text" class="w-input" id="det-service" placeholder="e.g. Mobile App Development">
      </div>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem">
      <button class="wbtn wbtn-ghost" onclick="closeDetailsModal()">Cancel</button>
      <button class="wbtn wbtn-primary" onclick="applyBatchDetails()">⚡ Apply Updates Everywhere</button>
    </div>
  </div>
</div>

<!-- Fullscreen Preview Modal -->
<div class="modal-overlay" id="fullscreen-modal">
  <div class="modal-box">
    <div class="modal-bar">
      <div style="font-weight:700; color:#fff" id="modal-title">Fullscreen Preview</div>
      <div style="display:flex; gap:0.75rem;">
        <button class="wbtn wbtn-primary" onclick="selectFromModal()">🚀 Select &amp; Edit</button>
        <button class="wbtn wbtn-ghost" onclick="closeFullscreenModal()">✕ Close</button>
      </div>
    </div>
    <iframe class="modal-iframe" id="modal-iframe"></iframe>
  </div>
</div>

<!-- API Key Modal -->
<div class="modal-overlay" id="apikey-modal">
  <div class="modal-box" style="max-width:540px; height:auto; padding:2rem;">
    <h2 style="font-size:1.3rem; margin-bottom:0.5rem; color:#fff">⚙️ Google Gemini API Key</h2>
    <p style="font-size:0.88rem; color:#94a3b8; line-height:1.6; margin-bottom:1.5rem">
      WebCraft AI uses Google Gemini for website generation and live refinements.
    </p>
    <div style="background:#0b0f17; border:1px solid #1e293b; border-radius:12px; padding:1rem; margin-bottom:1.25rem;">
      <div style="font-size:0.8rem; font-weight:700; color:#818cf8; margin-bottom:0.3rem">How to get your FREE API key:</div>
      <ol style="font-size:0.82rem; color:#cbd5e1; padding-left:1.2rem; line-height:1.6">
        <li>Visit <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:#38bdf8; text-decoration:underline">Google AI Studio</a></li>
        <li>Sign in &rarr; click <strong>"Create API key"</strong></li>
        <li>Copy your key (starts with <code>AIzaSy...</code>) and paste below:</li>
      </ol>
    </div>
    <div class="w-group">
      <label class="w-label" for="user-apikey-input">Your Gemini API Key</label>
      <input type="password" class="w-input" id="user-apikey-input" placeholder="AIzaSy...">
    </div>
    <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem">
      <button class="wbtn wbtn-ghost" onclick="closeApiKeyModal()">Cancel</button>
      <button class="wbtn wbtn-primary" onclick="saveApiKey()">Save API Key</button>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="payment-modal">
  <div class="modal-box" style="max-width:720px; height:auto; padding:2rem; max-height:92vh; overflow-y:auto">
    <div id="payment-step-plan">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
        <h2 style="font-size:1.35rem; color:#fff; display:flex; align-items:center; gap:0.5rem"><span>🚀</span> Publish Your Website</h2>
        <button class="wbtn wbtn-ghost" onclick="closePaymentModal()" style="padding:0.35rem 0.75rem">✕</button>
      </div>
      <p style="font-size:0.88rem; color:#94a3b8; margin-bottom:1.5rem">
        Your design is saved. Choose a plan to take it live with a real URL, SSL, and global CDN.
      </p>
      <div class="plan-grid" id="plan-grid">
        <div class="plan-card" data-plan="starter" data-price="9" onclick="selectPlan(this)">
          <div class="plan-name">Starter</div>
          <div class="plan-price">$9<small>/mo</small></div>
          <div class="plan-features">✅ Live URL<br>✅ Free SSL<br>✅ Global CDN<br>✅ 1 website</div>
        </div>
        <div class="plan-card selected" data-plan="pro" data-price="19" onclick="selectPlan(this)">
          <div class="plan-tag">Popular</div>
          <div class="plan-name">Pro</div>
          <div class="plan-price">$19<small>/mo</small></div>
          <div class="plan-features">✅ Custom domain<br>✅ SSL + CDN<br>✅ AI edits<br>✅ 5 websites<br>✅ Priority support</div>
        </div>
        <div class="plan-card" data-plan="business" data-price="49" onclick="selectPlan(this)">
          <div class="plan-name">Business</div>
          <div class="plan-price">$49<small>/mo</small></div>
          <div class="plan-features">✅ Everything in Pro<br>✅ Unlimited sites<br>✅ Team collab<br>✅ Analytics<br>✅ 24/7 support</div>
        </div>
      </div>
      <div class="pay-summary">
        <div class="row"><span>Plan</span><span id="pay-plan-name">Pro</span></div>
        <div class="row"><span>Billing cycle</span><span>Monthly</span></div>
        <div class="row"><span>Setup fee</span><span style="color:#34d399">FREE</span></div>
        <div class="row total"><span>Total due today</span><span id="pay-total">$19.00</span></div>
      </div>
      <div class="paypal-badge"><span>🅿️</span> Secured by PayPal · Buyer &amp; Seller Protection</div>
      <div class="w-label" style="margin-bottom:0.5rem">Payment Method</div>
      <div class="pay-methods">
        <div class="pay-method paypal-primary selected" data-method="paypal" onclick="selectPayMethod(this)">🅿️ Pay with PayPal</div>
        <div class="pay-method" data-method="paypal_card" onclick="selectPayMethod(this)">💳 Debit / Credit Card</div>
      </div>
      <div style="display:flex; justify-content:space-between; gap:0.75rem; margin-top:1.5rem; align-items:center; flex-wrap:wrap">
        <div style="font-size:0.75rem; color:#64748b">🔒 256-bit SSL · PCI-DSS compliant</div>
        <div style="display:flex; gap:0.75rem">
          <button class="wbtn wbtn-ghost" onclick="closePaymentModal()">Cancel</button>
          <button class="wbtn wbtn-generate" id="btn-pay-now" onclick="processPayment()">💳 Continue to PayPal</button>
        </div>
      </div>
    </div>

    <div id="payment-step-success" style="display:none">
      <div class="pay-success-anim">
        <div class="check">✓</div>
        <h2 style="font-size:1.6rem; color:#fff; margin-bottom:0.4rem">Payment Successful!</h2>
        <p style="font-size:0.92rem; color:#94a3b8">Your website is now <strong style="color:#34d399">live on the internet</strong> 🎉</p>
      </div>
      <div class="w-label">Your Live Website URL</div>
      <div class="live-url-box" id="live-url-box">Publishing…</div>
      <div style="display:flex; gap:0.75rem; margin-top:1.25rem; flex-wrap:wrap">
        <button class="wbtn wbtn-primary" onclick="window.open(document.getElementById('live-url-box').textContent.trim(),'_blank')">🌐 Visit Live Site</button>
        <button class="wbtn wbtn-ghost" onclick="copyLiveUrl()">📋 Copy URL</button>
        <button class="wbtn wbtn-ghost" onclick="closePaymentModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"><span id="toast-text"></span></div>

<!-- Puter.js & WebCraft AI Client Service -->
<script src="https://js.puter.com/v2/"></script>
<script src="<?= SITE_URL ?>/assets/js/puter-service.js"></script>

<script>
// ═════════════════════════════════════════════════════
//  GLOBAL STATE
// ═════════════════════════════════════════════════════
let generatedDesigns = [];
let activeDesignIndex = 0;
let currentHtml = '';
let modalViewingIndex = 0;

let selectedPlan      = { id:'pro', price:19, name:'Pro' };
let selectedPayMethod = 'paypal';
let currentOrderRef   = null;

const HISTORY_LIMIT = 5;

// ═════════════════════════════════════════════════════
//  INIT
// ═════════════════════════════════════════════════════
window.addEventListener('DOMContentLoaded', () => {
  const storedKey = localStorage.getItem('gemini_api_key');
  if (storedKey) document.getElementById('user-apikey-input').value = storedKey;

  const urlParams     = new URLSearchParams(window.location.search);
  const paymentStatus = urlParams.get('payment');
  const shouldResume  = urlParams.get('resume') === '1';
  const conceptParam  = parseInt(urlParams.get('concept') || '0', 10);

  if (paymentStatus === 'success' || paymentStatus === 'cancel' || shouldResume) {
    autoResumeProject(isNaN(conceptParam) ? 0 : conceptParam);
  } else {
    checkSavedProject();
  }

  if (paymentStatus === 'success') {
    setTimeout(() => handlePaymentReturn(), 900);
  } else if (paymentStatus === 'cancel') {
    showToast('⚠️ PayPal payment was cancelled. Your design is safely saved.');
  }

  if (urlParams.get('publish') === '1') {
    setTimeout(() => openPaymentModal(), 600);
  }

  restoreLiveStatus();

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeActionsMenu();
      closeDetailsModal();
      closeApiKeyModal();
      closeFullscreenModal();
    }
  });
  document.addEventListener('click', e => {
    const wrap = document.querySelector('.tb-menu-wrap');
    if (wrap && !wrap.contains(e.target)) closeActionsMenu();
  });
});

// ═════════════════════════════════════════════════════
//  AUTO-RESUME
// ═════════════════════════════════════════════════════
function autoResumeProject(conceptIndex = 0) {
  try {
    const raw = localStorage.getItem('webcraft_saved_project');
    if (!raw) { checkSavedProject(); return; }
    const p = JSON.parse(raw);
    if (!p || !p.designs || p.designs.length === 0) { checkSavedProject(); return; }

    generatedDesigns = p.designs;
    const idx = (conceptIndex >= 0 && conceptIndex < p.designs.length) ? conceptIndex : (p.activeDesignIndex || 0);

    if (p.bizName) document.getElementById('biz_name').value = p.bizName;

    const banner = document.getElementById('resume-project-banner');
    if (banner) banner.style.display = 'none';

    selectDesignAndEdit(idx);
    showToast(`📂 Resumed project: ${p.bizName || 'Website'}`);
  } catch (e) {
    console.warn('Auto-resume failed:', e);
    checkSavedProject();
  }
}

// ═════════════════════════════════════════════════════
//  WIZARD
// ═════════════════════════════════════════════════════
let currentWizStep = 1;
function wizNav(dir) {
  if (dir === 1 && !validateStep(currentWizStep)) return;
  currentWizStep = Math.max(1, Math.min(4, currentWizStep + dir));
  updateWizDisplay();
}

function validateStep(step) {
  if (step === 1) {
    const name = document.getElementById('biz_name').value.trim();
    const tag = document.getElementById('biz_tagline').value.trim();
    if (!name) { showToast('⚠️ Please enter your Business Name'); document.getElementById('biz_name').focus(); return false; }
    if (!tag)  { showToast('⚠️ Please enter your Core Mission or Tagline'); document.getElementById('biz_tagline').focus(); return false; }
  }
  return true;
}

function updateWizDisplay() {
  document.querySelectorAll('.wizard-step').forEach((s, idx) => {
    s.classList.toggle('active', idx + 1 === currentWizStep);
  });
  ['wp1','wp2','wp3','wp4'].forEach((id, idx) => {
    const dot = document.getElementById(id);
    dot.classList.toggle('active', idx + 1 === currentWizStep);
    dot.classList.toggle('done', idx + 1 < currentWizStep);
  });
  document.getElementById('wiz-prev').disabled = (currentWizStep === 1);
  document.getElementById('wiz-step-counter').textContent = `Step ${currentWizStep} of 4`;
  const isLast = (currentWizStep === 4);
  document.getElementById('wiz-next').style.display = isLast ? 'none' : 'inline-flex';
  document.getElementById('wiz-generate').style.display = isLast ? 'inline-flex' : 'none';
}

// ═════════════════════════════════════════════════════
//  GENERATE 3 DESIGNS (Backend API)
// ═════════════════════════════════════════════════════
async function generate3Designs() {
  const genBtn = document.getElementById('wiz-generate');
  genBtn.disabled = true;
  genBtn.innerHTML = '⚡ Generating 3 Designs…';

  const payload = {
    action: 'generate_3',
    api_key: localStorage.getItem('gemini_api_key') || '',
    data: {
      biz_name:      document.getElementById('biz_name').value.trim(),
      biz_type:      document.getElementById('biz_type').value,
      biz_tagline:   document.getElementById('biz_tagline').value.trim(),
      biz_audience:  document.getElementById('biz_audience').value.trim(),
      biz_services:  document.getElementById('biz_services').value.trim(),
      design_style:  document.querySelector('input[name="design_style"]:checked')?.value || 'modern',
      color_palette: document.querySelector('input[name="color_palette"]:checked')?.value || 'purple',
      biz_phone:     document.getElementById('biz_phone').value.trim(),
      biz_email:     document.getElementById('biz_email').value.trim(),
      biz_address:   document.getElementById('biz_address').value.trim(),
      sections:      Array.from(document.querySelectorAll('input[name="sections[]"]:checked')).map(i => i.value),
    }
  };

  try {
    const res = await fetch('<?= SITE_URL ?>/api/generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await res.json();

    if (result.success && result.designs && result.designs.length >= 3) {
      generatedDesigns = result.designs.map(d => ({ ...d, history: [] }));
      saveProjectToStorage();
      display3Designs(payload.data.biz_name);
      showToast('✨ 3 concepts generated!');
    } else {
      console.warn('Backend generation failed or key error, trying Puter AI (DeepSeek)...');
      showToast('⚡ Generating with Puter AI (DeepSeek)...');
      const puterDesigns = await window.PuterService.generateConceptsWithPuter(payload.data);
      if (puterDesigns && puterDesigns.length >= 3) {
        generatedDesigns = puterDesigns.map(d => ({ ...d, history: [] }));
        saveProjectToStorage();
        display3Designs(payload.data.biz_name);
        showToast('✨ 3 concepts generated with Puter AI!');
      } else {
        showToast('Error generating designs: ' + (result.error || 'Server error'));
      }
    }
  } catch (err) {
    console.warn('[generate3Designs fetch failed, trying Puter AI]:', err);
    try {
      showToast('⚡ Generating with Puter AI (DeepSeek)...');
      const puterDesigns = await window.PuterService.generateConceptsWithPuter(payload.data);
      if (puterDesigns && puterDesigns.length >= 3) {
        generatedDesigns = puterDesigns.map(d => ({ ...d, history: [] }));
        saveProjectToStorage();
        display3Designs(payload.data.biz_name);
        showToast('✨ 3 concepts generated with Puter AI!');
      } else {
        throw new Error('Puter AI generation returned incomplete designs.');
      }
    } catch (puterErr) {
      console.error('[Puter AI generation error]:', puterErr);
      showToast('Generation error: ' + puterErr.message);
    }
  } finally {
    genBtn.disabled = false;
    genBtn.innerHTML = '⚡ Generate 3 Concepts';
  }
}

function display3Designs(bizName) {
  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'block';
  document.getElementById('builder-screen').style.display = 'none';

  document.getElementById('designs-title').textContent = `3 Concepts for ${bizName}`;

  [0, 1, 2].forEach(i => {
    const d = generatedDesigns[i];
    const iframe = document.getElementById(`d${i+1}-iframe`);
    iframe.srcdoc = d.html;
    document.getElementById(`d${i+1}-name`).textContent = d.name;
    document.getElementById(`d${i+1}-desc`).textContent = d.description;
    document.getElementById(`d${i+1}-badge`).textContent = d.badge;
  });

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openFullscreenModal(index) {
  modalViewingIndex = index;
  const d = generatedDesigns[index];
  document.getElementById('modal-title').textContent = d.name;
  document.getElementById('modal-iframe').srcdoc = d.html;
  document.getElementById('fullscreen-modal').classList.add('active');
}
function closeFullscreenModal() {
  document.getElementById('fullscreen-modal').classList.remove('active');
}
function selectFromModal() {
  closeFullscreenModal();
  selectDesignAndEdit(modalViewingIndex);
}

// ═════════════════════════════════════════════════════
//  SELECT DESIGN & EDITOR
// ═════════════════════════════════════════════════════
function selectDesignAndEdit(index) {
  if (!generatedDesigns[index]) {
    console.warn('Invalid design index, defaulting to 0:', index);
    index = 0;
  }
  activeDesignIndex = index;
  currentHtml = generatedDesigns[index].html;

  document.getElementById('wizard-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'none';
  document.getElementById('builder-screen').style.display = 'flex';

  document.getElementById('tb-biz-badge').textContent = document.getElementById('biz_name').value || 'Website';

  [0, 1, 2].forEach(i => {
    document.getElementById(`tab-d${i}`).classList.toggle('active', i === index);
  });

  document.getElementById('det-tagline').value = document.getElementById('biz_tagline').value || '';
  document.getElementById('det-phone').value   = document.getElementById('biz_phone').value || '';
  document.getElementById('det-email').value   = document.getElementById('biz_email').value || '';
  document.getElementById('det-address').value = document.getElementById('biz_address').value || '';

  updateLiveIframe(currentHtml);
  updateUndoBtn();
}

function switchActiveDesign(index) {
  if (!generatedDesigns[index]) return;

  activeDesignIndex = index;
  currentHtml = generatedDesigns[index].html;

  [0, 1, 2].forEach(i => {
    document.getElementById(`tab-d${i}`).classList.toggle('active', i === index);
  });

  updateLiveIframe(currentHtml);
  updateUndoBtn();

  appendGeminiChatMessage(`Switched to <strong>${generatedDesigns[index].name}</strong>. Ready to preview or edit in Studio!`);
  showToast(`Switched to ${generatedDesigns[index].name}`);
}

function updateLiveIframe(html) {
  const iframe = document.getElementById('live-iframe');
  if (iframe) iframe.srcdoc = html;
}

function refreshLivePreview() {
  updateLiveIframe(currentHtml);
  showToast('Live preview refreshed');
}

function backToDesigns() {
  document.getElementById('builder-screen').style.display = 'none';
  document.getElementById('designs-screen').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ═════════════════════════════════════════════════════
//  OPEN STUDIO IN NEW TAB
// ═════════════════════════════════════════════════════
function openStudioInNewTab() {
  saveProjectToStorage();
  window.open('<?= SITE_URL ?>/studio.php?concept=' + activeDesignIndex, '_blank');
  showToast('🎨 Studio opened in new tab. Edits will auto-sync when you return.');
}

// Auto-sync when returning from Studio tab
window.addEventListener('focus', () => {
  try {
    const raw = localStorage.getItem('webcraft_saved_project');
    if (!raw) return;
    const p = JSON.parse(raw);
    if (p && p.designs && p.designs[activeDesignIndex]) {
      const savedHtml = p.designs[activeDesignIndex].html;
      if (savedHtml && savedHtml !== currentHtml) {
        pushHistory(activeDesignIndex, currentHtml);
        currentHtml = savedHtml;
        generatedDesigns = p.designs;
        if (!generatedDesigns[activeDesignIndex].history) {
          generatedDesigns[activeDesignIndex].history = [];
        }
        updateLiveIframe(currentHtml);
        updateUndoBtn();
        showToast('🔄 Synced with Studio edits!');
        appendGeminiChatMessage('🔄 Synced recent edits from the Studio tab.');
      }
    }
  } catch(e) {}
});

function setDevice(device) {
  const c = document.getElementById('preview-container');
  c.className = 'preview-container' + (device !== 'desktop' ? ' ' + device : '');
  ['desktop', 'tablet', 'mobile'].forEach(d => {
    document.getElementById(`d-${d}`).classList.toggle('active', d === device);
  });
}

// ═════════════════════════════════════════════════════
//  ACTIONS DROPDOWN
// ═════════════════════════════════════════════════════
function toggleActionsMenu(e) {
  if (e) e.stopPropagation();
  document.getElementById('tb-actions-menu').classList.toggle('open');
}
function closeActionsMenu() {
  document.getElementById('tb-actions-menu')?.classList.remove('open');
}

// ═════════════════════════════════════════════════════
//  AI EDIT HISTORY (UNDO)
// ═════════════════════════════════════════════════════
function pushHistory(designIndex, html) {
  const d = generatedDesigns[designIndex];
  if (!d) return;
  if (!Array.isArray(d.history)) d.history = [];
  if (d.history[d.history.length - 1] === html) return;
  d.history.push(html);
  if (d.history.length > HISTORY_LIMIT) d.history.shift();
}

function undoLastAiChange() {
  const d = generatedDesigns[activeDesignIndex];
  if (!d || !d.history || d.history.length === 0) {
    showToast('Nothing to undo');
    return;
  }
  const prev = d.history.pop();
  currentHtml = prev;
  d.html = prev;
  updateLiveIframe(currentHtml);
  saveProjectToStorage();
  updateUndoBtn();
  showToast('↶ Reverted the last AI change');
  appendGeminiChatMessage('↶ Reverted the last AI edit. Back to the previous version.');
}

function updateUndoBtn() {
  const d = generatedDesigns[activeDesignIndex];
  const btn = document.getElementById('btn-undo-ai');
  if (!btn) return;
  if (d && d.history && d.history.length > 0) {
    btn.style.display = 'inline-flex';
    btn.title = `Revert last AI change (${d.history.length} available)`;
  } else {
    btn.style.display = 'none';
  }
}

// ═════════════════════════════════════════════════════
//  PROJECT STORAGE
// ═════════════════════════════════════════════════════
function saveProjectToStorage() {
  try {
    if (!generatedDesigns || generatedDesigns.length === 0) return;
    const project = {
      bizName: document.getElementById('biz_name')?.value || 'Website',
      activeDesignIndex: activeDesignIndex,
      designs: generatedDesigns,
      timestamp: Date.now()
    };
    localStorage.setItem('webcraft_saved_project', JSON.stringify(project));
  } catch (e) { console.warn('Project storage save:', e); }
}

function checkSavedProject() {
  try {
    const raw = localStorage.getItem('webcraft_saved_project');
    if (!raw) return;
    const p = JSON.parse(raw);
    if (p && p.designs && p.designs.length >= 3) {
      const banner = document.getElementById('resume-project-banner');
      const nameEl = document.getElementById('resume-project-name');
      if (banner && nameEl) {
        nameEl.textContent = p.bizName || 'Previous Project';
        banner.style.display = 'flex';
      }
    }
  } catch (e) { console.warn('Check saved project:', e); }
}

function resumeSavedProject() {
  try {
    const raw = localStorage.getItem('webcraft_saved_project');
    if (!raw) return;
    const p = JSON.parse(raw);
    generatedDesigns = p.designs;
    activeDesignIndex = p.activeDesignIndex || 0;
    if (p.bizName) document.getElementById('biz_name').value = p.bizName;
    selectDesignAndEdit(activeDesignIndex);
    const banner = document.getElementById('resume-project-banner');
    if (banner) banner.style.display = 'none';
    showToast(`📂 Resumed saved project: ${p.bizName || 'Website'}`);
    appendGeminiChatMessage(`👋 Welcome back! Restored <strong>${p.bizName || 'Website'}</strong> with all previous edits intact.`);
  } catch (e) {
    showToast('Failed to restore project: ' + e.message);
  }
}

function dismissSavedProject() {
  const ok = window.confirm('⚠️ This will permanently delete your saved project from this browser.\n\nAre you sure?');
  if (!ok) return;

  const banner = document.getElementById('resume-project-banner');
  if (banner) banner.style.display = 'none';
  localStorage.removeItem('webcraft_saved_project');
  showToast('Saved project session cleared.');
}

// ═════════════════════════════════════════════════════
//  QUICK COLOR PALETTE
// ═════════════════════════════════════════════════════
function applyQuickColor(primary, secondary) {
  if (!currentHtml) return;
  pushHistory(activeDesignIndex, currentHtml);

  currentHtml = currentHtml.replace(/--primary:\s*[^;]+;/g, `--primary: ${primary};`);
  currentHtml = currentHtml.replace(/--primary-gradient:\s*[^;]+;/g, `--primary-gradient: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%);`);
  generatedDesigns[activeDesignIndex].html = currentHtml;
  updateLiveIframe(currentHtml);
  saveProjectToStorage();
  updateUndoBtn();

  appendGeminiChatMessage(`🎨 Updated website color theme to <strong>${primary}</strong>.`);
  showToast('🎨 Color theme updated');
}

// ═════════════════════════════════════════════════════
//  AI CO-PILOT CHAT (PRO REDESIGN)
// ═════════════════════════════════════════════════════
function getTimeStr() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function toggleChatHistory() {
  const log = document.getElementById('ai-chat-log');
  const btn = document.getElementById('chat-toggle-btn');
  if (!log) return;
  const isHidden = (log.style.display === 'none');
  log.style.display = isHidden ? 'flex' : 'none';
  if (btn) btn.textContent = isHidden ? '▾' : '▴';
}

function appendUserChatMessage(msg) {
  const log = document.getElementById('ai-chat-log');
  if (!log) return;
  const row = document.createElement('div');
  row.className = 'chat-row user-row';
  row.innerHTML = `
    <div class="chat-avatar user">You</div>
    <div class="chat-bubble-wrap">
      <div class="chat-msg user">${escapeHtml(msg)}</div>
      <div class="chat-timestamp">${getTimeStr()}</div>
    </div>`;
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}

function appendGeminiChatMessage(msg) {
  const log = document.getElementById('ai-chat-log');
  if (!log) return;
  const row = document.createElement('div');
  row.className = 'chat-row';
  row.innerHTML = `
    <div class="chat-avatar gemini">✦</div>
    <div class="chat-bubble-wrap">
      <div class="chat-msg ai">${msg}</div>
      <div class="chat-actions">
        <button class="chat-action-btn" onclick="copyChatMessage(this)">📋 Copy</button>
      </div>
      <div class="chat-timestamp">${getTimeStr()}</div>
    </div>`;
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}

function copyChatMessage(btn) {
  const bubble = btn.closest('.chat-bubble-wrap')?.querySelector('.chat-msg');
  if (!bubble) return;
  navigator.clipboard.writeText(bubble.innerText.trim()).then(() => {
    const old = btn.textContent;
    btn.textContent = '✓ Copied';
    setTimeout(() => btn.textContent = old, 1200);
  });
}

function clearChatLog() {
  const log = document.getElementById('ai-chat-log');
  if (!log) return;
  if (!confirm('Clear the conversation history?')) return;
  log.innerHTML = `
    <div class="chat-row">
      <div class="chat-avatar gemini">✦</div>
      <div class="chat-bubble-wrap">
        <div class="chat-msg ai">👋 Chat cleared. Ready for your next request!</div>
        <div class="chat-timestamp">just now</div>
      </div>
    </div>`;
}

function escapeHtml(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatMarkdown(text) {
  if (!text) return '';
  let escaped = escapeHtml(text);
  escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
  escaped = escaped.replace(/`([^`]+)`/g, '<code style="background:#1e293b;padding:0.1rem 0.35rem;border-radius:4px;color:#a5b4fc;font-size:0.75rem;">$1</code>');
  escaped = escaped.replace(/\n\n/g, '<br><br>');
  escaped = escaped.replace(/\n/g, '<br>');
  return escaped;
}

// ═════════════════════════════════════════════════════
//  PUTER.JS AUTH & ACCOUNT IN BUILDER
// ═════════════════════════════════════════════════════
window.addEventListener('puter-auth-changed', (e) => {
  const { user, isSignedIn } = e.detail || {};
  const dot = document.getElementById('b-puter-dot');
  const name = document.getElementById('b-puter-name');
  if (isSignedIn && user) {
    if (dot) dot.style.background = '#10b981';
    if (name) name.textContent = '@' + (user.username || 'Puter');
  } else {
    if (dot) dot.style.background = '#94a3b8';
    if (name) name.textContent = 'Sign In';
  }
});

async function toggleBuilderPuterMenu(e) {
  if (e) e.stopPropagation();
  const isSigned = await window.PuterService.isSignedIn();
  const user = await window.PuterService.getUser();

  const existing = document.getElementById('builder-puter-menu');
  if (existing) { existing.remove(); return; }

  const menu = document.createElement('div');
  menu.id = 'builder-puter-menu';
  menu.style.cssText = `
    position: fixed; bottom: 85px; right: 20px; z-index: 100060;
    background: #111726; border: 1.5px solid #283347; border-radius: 14px;
    padding: 1rem; width: 270px; box-shadow: 0 15px 40px rgba(0,0,0,0.7);
    color: #f8fafc; font-family: inherit; font-size: 0.82rem;
  `;

  if (isSigned && user) {
    menu.innerHTML = `
      <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #1e293b;">
        <div style="width:34px;height:34px;border-radius:50%;background:#4f46e5;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;">👤</div>
        <div>
          <div style="font-weight:700;color:#fff;">@${escapeHtml(user.username || 'User')}</div>
          <div style="font-size:0.68rem;color:#10b981;">🟢 Puter.js AI Active</div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:0.45rem;">
        <button class="tb-btn" onclick="handleBuilderPuterSwitch()" style="justify-content:flex-start;width:100%;">🔄 Switch Account</button>
        <button class="tb-btn" onclick="window.PuterService.openSignUp()" style="justify-content:flex-start;width:100%;">➕ Create Free Account</button>
        <button class="tb-btn" onclick="handleBuilderPuterSignOut()" style="justify-content:flex-start;width:100%;color:#f43f5e;border-color:#3f1826;">🚪 Sign Out</button>
      </div>
    `;
  } else {
    menu.innerHTML = `
      <div style="margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #1e293b;">
        <div style="font-weight:700;color:#fff;margin-bottom:0.2rem;">Puter AI Integration</div>
        <div style="font-size:0.72rem;color:#94a3b8;line-height:1.4;">Sign in to unlock free DeepSeek V3 generations &amp; chat.</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:0.45rem;">
        <button class="tb-btn" onclick="handleBuilderPuterSignIn()" style="justify-content:center;width:100%;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">✦ Sign In with Puter</button>
        <button class="tb-btn" onclick="window.PuterService.openSignUp()" style="justify-content:center;width:100%;">➕ Create Free Account</button>
      </div>
    `;
  }

  document.body.appendChild(menu);
  const closeMenu = (ev) => {
    if (!menu.contains(ev.target) && ev.target.id !== 'builder-puter-btn') {
      menu.remove();
      document.removeEventListener('click', closeMenu);
    }
  };
  setTimeout(() => document.addEventListener('click', closeMenu), 10);
}

async function handleBuilderPuterSignIn() {
  document.getElementById('builder-puter-menu')?.remove();
  try {
    await window.PuterService.signIn();
    showToast('✓ Signed in with Puter!');
  } catch (e) {
    showToast('Sign in cancelled');
  }
}

async function handleBuilderPuterSwitch() {
  document.getElementById('builder-puter-menu')?.remove();
  try {
    await window.PuterService.switchAccount();
    showToast('✓ Switched Puter account!');
  } catch (e) {
    showToast('Account switch cancelled');
  }
}

async function handleBuilderPuterSignOut() {
  document.getElementById('builder-puter-menu')?.remove();
  await window.PuterService.signOut();
  showToast('Signed out of Puter');
}

function quickRefine(promptText) {
  document.getElementById('refine-query').value = promptText;
  executeRefine();
}

// ═════════════════════════════════════════════════════
//  REFINE (Puter.js AI & Gemini Fallback)
// ═════════════════════════════════════════════════════
async function executeRefine() {
  const inputEl = document.getElementById('refine-query');
  const query = inputEl.value.trim();
  if (!query) return;

  const snapshotHtml = currentHtml;
  const snapshotDesignIndex = activeDesignIndex;
  const currentConceptName = generatedDesigns[activeDesignIndex]?.name || `Concept ${activeDesignIndex + 1}`;

  appendUserChatMessage(query);
  inputEl.value = '';

  const btn = document.getElementById('btn-refine');
  btn.disabled = true;
  btn.innerHTML = '<span>⏳</span><span>Thinking…</span>';

  const thinking = document.getElementById('chat-thinking');
  if (thinking) thinking.style.display = 'flex';
  const chatLog = document.getElementById('ai-chat-log');
  if (chatLog) chatLog.scrollTop = chatLog.scrollHeight;

  try {
    // 1. First attempt with PuterService (DeepSeek V3 client-side)
    const res = await window.PuterService.chatAndEdit({
      userPrompt: query,
      selectedElement: null,
      currentHtml: currentHtml,
      context: {
        bizName: document.getElementById('biz_name')?.value || 'Website',
        summary: `Concept: ${currentConceptName}`
      }
    });

    // Conversational response
    appendGeminiChatMessage(formatMarkdown(res.conversation));

    // If edit was generated
    if (res.isEdit && res.updatedHtml) {
      pushHistory(snapshotDesignIndex, snapshotHtml);

      let newHtml = res.updatedHtml;
      if (newHtml.includes('<html') || newHtml.includes('<!DOCTYPE')) {
        currentHtml = newHtml;
      } else {
        currentHtml = currentHtml.replace('</body>', `${newHtml}\n</body>`);
      }
      generatedDesigns[snapshotDesignIndex].html = currentHtml;
      saveProjectToStorage();
      updateLiveIframe(currentHtml);
      updateUndoBtn();

      const previewBox = document.getElementById('preview-container');
      if (previewBox) {
        previewBox.style.transition = 'box-shadow 0.3s ease';
        previewBox.style.boxShadow = '0 0 35px rgba(99,102,241,0.65)';
        setTimeout(() => { previewBox.style.boxShadow = ''; }, 1200);
      }
      showToast(`✨ ${currentConceptName} updated!`);
    }

  } catch (puterErr) {
    console.warn('[Puter AI error in builder]:', puterErr);
    if (window.PuterService.isQuotaOrCreditError(puterErr)) {
      appendGeminiChatMessage('⚠️ Puter AI credit limit reached on this account. Please switch accounts or create a new free Puter account using the button above.');
    } else {
      // Backend Gemini fallback
      try {
        await executeGeminiBackendRefine(query, snapshotHtml, snapshotDesignIndex, currentConceptName);
      } catch (geminiErr) {
        appendGeminiChatMessage(`⚠️ AI request failed: ${escapeHtml(geminiErr.message)}`);
        showToast('Error: ' + geminiErr.message);
      }
    }
  } finally {
    if (thinking) thinking.style.display = 'none';
    btn.disabled = false;
    btn.innerHTML = '<span>✦</span><span>Refine</span>';
  }
}

async function executeGeminiBackendRefine(query, snapshotHtml, snapshotDesignIndex, currentConceptName) {
  const res = await fetch('<?= SITE_URL ?>/api/generate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'refine',
      api_key: localStorage.getItem('gemini_api_key') || '',
      current_html: currentHtml,
      instruction: query,
      concept_index: activeDesignIndex,
      concept_name: currentConceptName,
      biz_name: document.getElementById('biz_name')?.value || 'Website'
    })
  });
  const result = await res.json();
  if (result.success && result.html) {
    pushHistory(snapshotDesignIndex, snapshotHtml);
    currentHtml = result.html;
    generatedDesigns[snapshotDesignIndex].html = currentHtml;
    saveProjectToStorage();
    updateLiveIframe(currentHtml);
    updateUndoBtn();
    appendGeminiChatMessage(`✨ ${escapeHtml(result.response_msg || 'Updated via Gemini')}`);
    showToast(`✨ ${currentConceptName} updated!`);
  } else {
    throw new Error(result.error || 'Backend failed');
  }
}

document.getElementById('refine-query').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); executeRefine(); }
});

// ═════════════════════════════════════════════════════
//  UPDATE DETAILS MODAL (Backend API)
// ═════════════════════════════════════════════════════
function openDetailsModal()  { document.getElementById('details-modal').classList.add('active'); }
function closeDetailsModal() { document.getElementById('details-modal').classList.remove('active'); }

async function applyBatchDetails() {
  const details = {
    tagline:     document.getElementById('det-tagline').value.trim(),
    phone:       document.getElementById('det-phone').value.trim(),
    email:       document.getElementById('det-email').value.trim(),
    address:     document.getElementById('det-address').value.trim(),
    cta_text:    document.getElementById('det-cta').value.trim(),
    new_service: document.getElementById('det-service').value.trim(),
  };

  closeDetailsModal();
  appendUserChatMessage('Updating core website details…');

  const snapshotHtml = currentHtml;
  const currentConceptName = generatedDesigns[activeDesignIndex]?.name || `Concept ${activeDesignIndex + 1}`;

  try {
    const res = await fetch('<?= SITE_URL ?>/api/generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update_details',
        api_key: localStorage.getItem('gemini_api_key') || '',
        current_html: currentHtml,
        details: details,
        concept_index: activeDesignIndex,
        concept_name: currentConceptName
      })
    });
    const result = await res.json();

    if (result.success && result.html) {
      pushHistory(activeDesignIndex, snapshotHtml);

      currentHtml = result.html;
      generatedDesigns[activeDesignIndex].html = currentHtml;
      saveProjectToStorage();
      updateLiveIframe(currentHtml);
      updateUndoBtn();

      appendGeminiChatMessage(`✅ All business details synced across <strong style="color:#a5b4fc;">${escapeHtml(currentConceptName)}</strong>.`);
      showToast('✅ All details updated!');
    } else {
      currentHtml = snapshotHtml;
      showToast('Notice: ' + (result.error || 'Failed to apply details'));
    }
  } catch (err) {
    currentHtml = snapshotHtml;
    showToast('Error updating details: ' + err.message);
  }
}

function openInNewTab() {
  const blob = new Blob([currentHtml], { type: 'text/html;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  window.open(url, '_blank');
}

// ── API Key Modal ──
function openApiKeyModal()  { document.getElementById('apikey-modal').classList.add('active'); }
function closeApiKeyModal() { document.getElementById('apikey-modal').classList.remove('active'); }

function saveApiKey() {
  const key = document.getElementById('user-apikey-input').value.trim();
  if (key) {
    localStorage.setItem('gemini_api_key', key);
    showToast('✅ Gemini API Key saved!');
    appendGeminiChatMessage('🔑 Your Google Gemini API Key has been saved locally.');
  } else {
    localStorage.removeItem('gemini_api_key');
    showToast('API Key cleared.');
  }
  closeApiKeyModal();
}

// ── Toast ──
let toastTimeout;
function showToast(text) {
  const t = document.getElementById('toast');
  document.getElementById('toast-text').textContent = text;
  t.classList.add('show');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => t.classList.remove('show'), 3200);
}

// ═════════════════════════════════════════════════════
//  PAYMENT FLOW
// ═════════════════════════════════════════════════════
function saveAndProceedToPayment() {
  saveProjectToStorage();
  persistDesignToServer().catch(() => {});
  openPaymentModal();
}

async function persistDesignToServer() {
  try {
    const payload = {
      action:        'save_design',
      api_key:       localStorage.getItem('gemini_api_key') || '',
      biz_name:      document.getElementById('biz_name')?.value || 'Website',
      concept_index: activeDesignIndex,
      html:          currentHtml
    };
    const res = await fetch('<?= SITE_URL ?>/api/generate.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const j = await res.json();
    if (j && j.design_id) localStorage.setItem('webcraft_design_id', j.design_id);
  } catch (e) { console.warn('Server-side design save skipped:', e); }
}

function openPaymentModal() {
  document.getElementById('payment-step-plan').style.display    = 'block';
  document.getElementById('payment-step-success').style.display = 'none';
  document.getElementById('payment-modal').classList.add('active');
}
function closePaymentModal() {
  document.getElementById('payment-modal').classList.remove('active');
}

function selectPlan(el) {
  document.querySelectorAll('#plan-grid .plan-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedPlan = {
    id:    el.dataset.plan,
    price: parseFloat(el.dataset.price),
    name:  el.querySelector('.plan-name').textContent
  };
  document.getElementById('pay-plan-name').textContent = selectedPlan.name;
  document.getElementById('pay-total').textContent     = '$' + selectedPlan.price.toFixed(2);
}

function selectPayMethod(el) {
  document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('selected'));
  el.classList.add('selected');
  selectedPayMethod = el.dataset.method;
}

async function processPayment() {
  const btn = document.getElementById('btn-pay-now');
  btn.disabled = true;
  btn.textContent = '⏳ Creating secure PayPal order…';

  saveProjectToStorage();

  try {
    const res = await fetch('<?= SITE_URL ?>/api/payment.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action:    'create_order',
        plan:      selectedPlan.id,
        amount:    selectedPlan.price,
        method:    selectedPayMethod,
        biz_name:  document.getElementById('biz_name')?.value || 'Website',
        design_id: localStorage.getItem('webcraft_design_id') || null,
        html:      currentHtml,
      }),
    });

    const j = await res.json();

    if (j.success && j.checkout_url) {
      currentOrderRef = j.order_ref;
      localStorage.setItem('webcraft_pending_order_ref', j.order_ref);
      window.location.href = j.checkout_url;
      return;
    }

    showToast('⚠️ ' + (j.error || 'Could not reach PayPal. Please try again.'));
    btn.disabled = false;
    btn.textContent = '💳 Continue to PayPal';

  } catch (err) {
    showToast('Network error: ' + err.message);
    btn.disabled = false;
    btn.textContent = '💳 Continue to PayPal';
  }
}

async function handlePaymentReturn() {
  document.getElementById('payment-step-plan').style.display    = 'none';
  document.getElementById('payment-step-success').style.display = 'block';
  document.getElementById('payment-modal').classList.add('active');
  document.getElementById('live-url-box').textContent = 'Confirming PayPal payment…';

  const params        = new URLSearchParams(window.location.search);
  const paypalOrderId = params.get('token');

  if (!paypalOrderId) {
    document.getElementById('live-url-box').textContent = '⚠️ Missing PayPal order ID';
    showToast('⚠️ Payment return missing order token');
    return;
  }

  window.history.replaceState({}, '', window.location.pathname);

  try {
    const res = await fetch('<?= SITE_URL ?>/api/payment.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action:          'capture_order',
        paypal_order_id: paypalOrderId,
      }),
    });

    const j = await res.json();

    if (j.success && j.live_url) {
      document.getElementById('live-url-box').textContent = j.live_url;

      const pill = document.getElementById('publish-status');
      if (pill) {
        pill.style.display = 'inline-flex';
        pill.classList.add('live');
        pill.innerHTML = `🟢 Live · <a href="${j.live_url}" target="_blank" rel="noopener">${j.live_url}</a>`;
      }

      localStorage.setItem('webcraft_live_url', j.live_url);
      localStorage.removeItem('webcraft_pending_order_ref');
      saveProjectToStorage();

      showToast('🎉 Payment captured — website is live!');
      appendGeminiChatMessage(
        `🚀 <strong>Payment confirmed via PayPal!</strong> Your website is now live at ` +
        `<a href="${j.live_url}" target="_blank" style="color:#6ee7b7">${j.live_url}</a>`
      );
    } else {
      document.getElementById('live-url-box').textContent = '⚠️ ' + (j.error || 'Capture failed');
      showToast('⚠️ ' + (j.error || 'Payment capture failed'));
    }
  } catch (err) {
    document.getElementById('live-url-box').textContent = '⚠️ Network error';
    showToast('Capture error: ' + err.message);
  }
}

function copyLiveUrl() {
  const url = document.getElementById('live-url-box').textContent.trim();
  navigator.clipboard.writeText(url).then(() => showToast('📋 Live URL copied!'));
}

function restoreLiveStatus() {
  const url = localStorage.getItem('webcraft_live_url');
  if (!url) return;
  const pill = document.getElementById('publish-status');
  if (pill) {
    pill.style.display = 'inline-flex';
    pill.classList.add('live');
    pill.innerHTML = `🟢 Live · <a href="${url}" target="_blank" rel="noopener">${url}</a>`;
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>