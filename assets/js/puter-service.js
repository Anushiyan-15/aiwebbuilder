/**
 * WebCraft AI — Puter.js Service
 * 
 * Provides client-side free AI Chat, Conversational Co-Pilot,
 * Selected Element In-Place Editing, and Puter Account/Credit Management
 * (Sign In, Switch Account, Sign Up, Quota limit recovery).
 */

(function (window) {
  'use strict';

  const PUTER_MODELS = [
    { id: 'deepseek/deepseek-chat', name: 'DeepSeek Chat (V3)', badge: 'Recommended' },
    { id: 'gpt-4o-mini',            name: 'OpenAI GPT-4o Mini',  badge: 'Fast' },
    { id: 'claude-3-5-sonnet',      name: 'Claude 3.5 Sonnet',   badge: 'Smart' },
    { id: 'gemini-2.0-flash',       name: 'Gemini 2.0 Flash',    badge: 'Quick' }
  ];

  class PuterService {
    constructor() {
      this.currentUser = null;
      this.selectedModel = localStorage.getItem('webcraft_puter_model') || 'deepseek/deepseek-chat';
      this.chatHistory = [];
      this.initAuthListeners();
    }

    /**
     * Ensure puter.js is loaded
     */
    async ensureReady() {
      if (typeof window.puter !== 'undefined') {
        return window.puter;
      }
      return new Promise((resolve, reject) => {
        let attempts = 0;
        const interval = setInterval(() => {
          attempts++;
          if (typeof window.puter !== 'undefined') {
            clearInterval(interval);
            resolve(window.puter);
          } else if (attempts > 50) {
            clearInterval(interval);
            reject(new Error('Puter.js library (https://js.puter.com/v2/) failed to load. Please check your internet connection.'));
          }
        }, 100);
      });
    }

    /**
     * Check if user is signed in
     */
    async isSignedIn() {
      try {
        await this.ensureReady();
        return window.puter.auth.isSignedIn();
      } catch (e) {
        return false;
      }
    }

    /**
     * Fetch currently authenticated user
     */
    async getUser() {
      try {
        await this.ensureReady();
        if (window.puter.auth.isSignedIn()) {
          this.currentUser = await window.puter.auth.getUser();
          return this.currentUser;
        }
      } catch (e) {
        console.warn('[PuterService] getUser failed:', e);
      }
      this.currentUser = null;
      return null;
    }

    /**
     * Prompt user to sign in
     */
    async signIn() {
      await this.ensureReady();
      try {
        const user = await window.puter.auth.signIn();
        this.currentUser = user || await window.puter.auth.getUser();
        this.broadcastAuthChange();
        return this.currentUser;
      } catch (err) {
        console.error('[PuterService] Sign in error:', err);
        throw err;
      }
    }

    /**
     * Sign out current user
     */
    async signOut() {
      await this.ensureReady();
      try {
        await window.puter.auth.signOut();
        this.currentUser = null;
        this.broadcastAuthChange();
      } catch (err) {
        console.warn('[PuterService] Sign out error:', err);
      }
    }

    /**
     * Switch account: Logs out current user and prompts sign-in popup to choose or create another account
     */
    async switchAccount() {
      await this.ensureReady();
      try {
        await window.puter.auth.signOut();
        this.currentUser = null;
        this.broadcastAuthChange();
        // Request auth with account prompt
        const user = await window.puter.auth.signIn({ request_auth: true });
        this.currentUser = user || await window.puter.auth.getUser();
        this.broadcastAuthChange();
        return this.currentUser;
      } catch (err) {
        console.error('[PuterService] Switch account error:', err);
        throw err;
      }
    }

    /**
     * Open Puter sign-up in new tab/window
     */
    openSignUp() {
      window.open('https://puter.com/signup', '_blank', 'width=600,height=700');
    }

    /**
     * Broadcast auth state changes to UI
     */
    broadcastAuthChange() {
      window.dispatchEvent(new CustomEvent('puter-auth-changed', {
        detail: { user: this.currentUser, isSignedIn: !!this.currentUser }
      }));
    }

    initAuthListeners() {
      window.addEventListener('load', async () => {
        try {
          await this.getUser();
          this.broadcastAuthChange();
        } catch (e) {}
      });
    }

    /**
     * Detect if an error is due to credit exhaustion or quota limits
     */
    isQuotaOrCreditError(err) {
      if (!err) return false;
      const msg = (typeof err === 'string' ? err : (err.message || err.error || JSON.stringify(err))).toLowerCase();
      const code = err.code || err.status || 0;
      return (
        code === 429 ||
        code === 402 ||
        msg.includes('quota') ||
        msg.includes('credit') ||
        msg.includes('limit') ||
        msg.includes('insufficient') ||
        msg.includes('exceeded') ||
        msg.includes('payment') ||
        msg.includes('billing')
      );
    }

    /**
     * Show Credit Limit & Switch Account Modal
     */
    showCreditExhaustedModal(opts = {}) {
      let modal = document.getElementById('puter-credits-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'puter-credits-modal';
        modal.className = 'modal-overlay';
        modal.style.cssText = `
          position: fixed; inset: 0; z-index: 100050;
          background: rgba(4, 7, 14, 0.85); backdrop-filter: blur(8px);
          display: flex; align-items: center; justify-content: center;
          padding: 1.5rem; animation: pFadeIn 0.2s ease;
        `;
        document.body.appendChild(modal);
      }

      modal.innerHTML = `
        <div style="
          background: #111726; border: 1.5px solid #312e81; border-radius: 20px;
          max-width: 520px; width: 100%; padding: 2rem; color: #f8fafc;
          box-shadow: 0 25px 60px -15px rgba(0,0,0,0.8), 0 0 30px rgba(99,102,241,0.25);
          font-family: inherit; position: relative;
        ">
          <button type="button" onclick="document.getElementById('puter-credits-modal').style.display='none'" style="
            position: absolute; top: 1rem; right: 1rem; background: none; border: none;
            color: #94a3b8; font-size: 1.3rem; cursor: pointer; padding: 0.25rem 0.5rem;
          ">✕</button>

          <div style="display:flex; align-items:center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="
              width: 48px; height: 48px; border-radius: 12px;
              background: linear-gradient(135deg, #f59e0b, #ef4444);
              display: flex; align-items: center; justify-content: center;
              font-size: 1.6rem;
            ">⚡</div>
            <div>
              <h3 style="font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0;">Puter AI Credits Exhausted</h3>
              <p style="font-size: 0.8rem; color: #cbd5e1; margin: 0.15rem 0 0;">Free credit quota reached on this account</p>
            </div>
          </div>

          <p style="font-size: 0.88rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem;">
            Your current Puter account <strong>${this.currentUser?.username ? '@' + this.currentUser.username : ''}</strong> has reached its free AI credit limit. 
            You can <strong>switch to another Puter account</strong> or <strong>create a new free account</strong> in 30 seconds to continue editing and generating for free!
          </p>

          <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
            <button id="p-btn-switch-account" style="
              display: flex; align-items: center; justify-content: center; gap: 0.6rem;
              padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 700; font-size: 0.92rem;
              background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border: none;
              cursor: pointer; box-shadow: 0 4px 15px rgba(79,70,229,0.4); transition: transform 0.15s;
            ">
              <span>🔄</span> Switch Puter Account (Sign into another)
            </button>

            <button id="p-btn-signup-new" style="
              display: flex; align-items: center; justify-content: center; gap: 0.6rem;
              padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 700; font-size: 0.92rem;
              background: #1e1b4b; border: 1.5px solid #6366f1; color: #c7d2fe;
              cursor: pointer; transition: background 0.15s;
            ">
              <span>➕</span> Create New Free Puter Account
            </button>

            ${typeof window.openApiKeyModal === 'function' ? `
            <button id="p-btn-use-gemini" style="
              display: flex; align-items: center; justify-content: center; gap: 0.6rem;
              padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.85rem;
              background: transparent; border: 1.5px solid #334155; color: #94a3b8;
              cursor: pointer;
            ">
              <span>🔑</span> Use Custom Gemini API Key Instead
            </button>` : ''}
          </div>

          <div style="font-size: 0.72rem; color: #64748b; text-align: center;">
            Tip: Each new Puter account gets its own free AI tier with instant activation.
          </div>
        </div>
      `;

      modal.style.display = 'flex';

      document.getElementById('p-btn-switch-account')?.addEventListener('click', async () => {
        try {
          modal.style.display = 'none';
          await this.switchAccount();
          if (opts.onSuccess) opts.onSuccess();
        } catch (e) {
          console.error(e);
        }
      });

      document.getElementById('p-btn-signup-new')?.addEventListener('click', () => {
        this.openSignUp();
      });

      document.getElementById('p-btn-use-gemini')?.addEventListener('click', () => {
        modal.style.display = 'none';
        if (typeof window.openApiKeyModal === 'function') {
          window.openApiKeyModal();
        }
      });
    }

    /**
     * Send AI Chat & Edit request via Puter.js
     * 
     * Supports:
     * - Pure conversational chat (returns chat response)
     * - Selected element editing (returns chat response + updated element HTML)
     * - Whole page modification (returns chat response + updated page HTML)
     */
    async chatAndEdit({
      userPrompt,
      selectedElement = null,
      currentHtml = '',
      context = {},
      onTyping = null
    }) {
      await this.ensureReady();

      // Ensure user is signed in
      const signedIn = await this.isSignedIn();
      if (!signedIn) {
        try {
          await this.signIn();
        } catch (err) {
          throw new Error('Puter sign-in was cancelled. Please sign in to proceed with AI generation & editing.');
        }
      }

      const hasSelected = !!selectedElement;
      const selectedHtml = hasSelected ? (selectedElement.html || '') : '';
      const selectedTag = hasSelected ? (selectedElement.tag || 'div') : '';

      let systemPrompt = '';
      let userMessage = '';

      if (hasSelected) {
        systemPrompt = `You are WebCraft AI, an elite web designer and front-end architect.
The user is visually designing a website and has clicked and SELECTED a specific HTML element on the canvas.

YOUR TASK:
1. Converse naturally, helpfully, and professionally with the user.
2. If the user asks for a change, edit, styling, copy change, or improvement to what they have selected, modify the selected element's HTML to fulfill their request.
3. If the user is just asking a question, advice, or ideas, answer conversationally and do NOT provide any HTML code block.

FORMAT REQUIREMENTS:
Always structure your output with these two sections:

---CONVERSATION---
[Your friendly, concise response in markdown explaining what you did or answering their question. Use bullet points or emojis if helpful.]

---UPDATED_HTML---
\`\`\`html
[Return ONLY the updated HTML for the selected element (${selectedTag}). Preserve essential classes, tags, and inline styles unless requested to change. Do NOT wrap in <html>, <body>, or extra outer sections.]
\`\`\`
(Note: If the user only asked a question without requesting an edit, write NONE under ---UPDATED_HTML---)`;

        userMessage = `CURRENT SELECTED ELEMENT (${selectedTag}):
${selectedHtml}

USER INSTRUCTION:
${userPrompt}`;
      } else {
        systemPrompt = `You are WebCraft AI, an elite web designer and front-end architect.
The user is building a website. No single element is selected, so they are either asking a question, asking for ideas, or asking to update the entire page / add sections.

YOUR TASK:
1. Converse warmly, smartly, and helpfully with the user.
2. If they ask to add a section, change site colors, or update the website, provide updated/new HTML or guidance.
3. If they ask a general question, answer conversationally.

FORMAT REQUIREMENTS:
---CONVERSATION---
[Your conversational answer in markdown]

---UPDATED_HTML---
[If adding a section or updating the page, provide the complete clean HTML snippet inside \`\`\`html ... \`\`\`. Otherwise, write NONE.]`;

        userMessage = `WEBSITE CONTEXT:
Business Name: ${context.bizName || 'Website'}
Current Snippet/DOM info: ${context.summary || 'Responsive modern website'}

USER INSTRUCTION:
${userPrompt}`;
      }

      const messages = [
        { role: 'system', content: systemPrompt },
        ...this.chatHistory.slice(-6),
        { role: 'user', content: userMessage }
      ];

      try {
        if (onTyping) onTyping(true);

        const modelToUse = this.selectedModel || 'deepseek/deepseek-chat';
        console.log(`[PuterService] Calling puter.ai.chat with model: ${modelToUse}`);

        const response = await window.puter.ai.chat(messages, {
          model: modelToUse
        });

        const rawText = typeof response === 'string'
          ? response
          : (response?.message?.content || response?.text || '');

        if (!rawText) {
          throw new Error('Empty response received from AI.');
        }

        // Add to history
        this.chatHistory.push({ role: 'user', content: userPrompt });
        this.chatHistory.push({ role: 'assistant', content: rawText });

        return this.parseAiResponse(rawText, hasSelected);
      } catch (err) {
        console.error('[PuterService] AI request error:', err);
        if (this.isQuotaOrCreditError(err)) {
          this.showCreditExhaustedModal();
          throw new Error('Puter AI credit quota exceeded for this account. Please switch accounts or create a new free Puter account.');
        }
        throw err;
      } finally {
        if (onTyping) onTyping(false);
      }
    }

    /**
     * Parse structured AI response
     */
    parseAiResponse(rawText, hasSelected) {
      let conversation = '';
      let updatedHtml = null;
      let isEdit = false;

      if (rawText.includes('---CONVERSATION---')) {
        const parts = rawText.split('---UPDATED_HTML---');
        const convPart = parts[0].replace('---CONVERSATION---', '').trim();
        conversation = convPart;

        if (parts[1]) {
          const htmlPart = parts[1].trim();
          if (htmlPart !== 'NONE' && htmlPart.includes('```')) {
            const match = htmlPart.match(/```(?:html)?\s*([\s\S]*?)\s*```/i);
            if (match && match[1] && match[1].trim().length > 5) {
              updatedHtml = match[1].trim();
              isEdit = true;
            }
          }
        }
      } else {
        // Fallback parsing if model did not use exact headers
        const codeMatch = rawText.match(/```(?:html)?\s*([\s\S]*?)\s*```/i);
        if (codeMatch && codeMatch[1]) {
          updatedHtml = codeMatch[1].trim();
          conversation = rawText.replace(/```(?:html)?\s*[\s\S]*?\s*```/gi, '').trim();
          isEdit = true;
        } else {
          conversation = rawText;
          isEdit = false;
        }
      }

      if (!conversation) {
        conversation = isEdit ? '✨ Updated as requested.' : 'Here is what I found for you.';
      }

      return {
        conversation,
        updatedHtml,
        isEdit,
        rawText
      };
    }

    /**
     * Generate 3 Bespoke Website Concepts using Puter AI
     */
    async generateConceptsWithPuter(bizData) {
      await this.ensureReady();
      const signedIn = await this.isSignedIn();
      if (!signedIn) {
        await this.signIn();
      }

      const bizName = bizData.biz_name || 'Zenith Studio';
      const bizType = bizData.biz_type || 'Digital Agency';
      const tagline = bizData.biz_tagline || 'Elevate your online presence';
      const services = bizData.biz_services || 'Web Design, Strategy, Branding';
      const color = bizData.color_palette || 'purple';
      const style = bizData.design_style || 'modern';

      const prompt = `You are an elite creative director and principal front-end engineer.
Generate 3 distinct, beautiful, responsive, modern, production-grade landing page website concepts for this business:
Business Name: ${bizName}
Industry/Type: ${bizType}
Tagline: ${tagline}
Key Services: ${services}
Theme Style: ${style}
Color Scheme: ${color}

REQUIREMENTS:
1. Provide THREE completely unique concepts (Concept 1: Modern & Bold, Concept 2: Elegant & Minimal, Concept 3: Dynamic & Conversion-Focused).
2. Each concept must be a COMPLETE, standalone, fully styled HTML page with <!DOCTYPE html>, inline <style> block, modern responsive layout, Hero with CTA button, Services, About, Testimonials/Stats, Contact form, and Footer.
3. Separate the three concepts with exact markers:
===CONCEPT_1_START===
[Full HTML for Concept 1]
===CONCEPT_1_END===

===CONCEPT_2_START===
[Full HTML for Concept 2]
===CONCEPT_2_END===

===CONCEPT_3_START===
[Full HTML for Concept 3]
===CONCEPT_3_END===`;

      try {
        const modelToUse = this.selectedModel || 'deepseek/deepseek-chat';
        console.log(`[PuterService] Generating 3 concepts with ${modelToUse}...`);
        
        const response = await window.puter.ai.chat(prompt, {
          model: modelToUse
        });

        const raw = typeof response === 'string' ? response : (response?.message?.content || response?.text || '');
        
        const extract = (startTag, endTag) => {
          if (!raw.includes(startTag)) return null;
          const part = raw.split(startTag)[1];
          if (!part) return null;
          const html = part.split(endTag)[0];
          return html ? html.replace(/```(?:html)?/g, '').replace(/```/g, '').trim() : null;
        };

        const c1 = extract('===CONCEPT_1_START===', '===CONCEPT_1_END===');
        const c2 = extract('===CONCEPT_2_START===', '===CONCEPT_2_END===');
        const c3 = extract('===CONCEPT_3_START===', '===CONCEPT_3_END===');

        if (c1 && c2 && c3) {
          return [
            { name: `${bizName} — Bold Modern`, badge: 'High Impact', description: 'Contemporary layout with rich typography and dynamic accents.', html: c1 },
            { name: `${bizName} — Clean Minimal`, badge: 'Minimalist', description: 'Refined spacing, elegant lines, and subtle micro-interactions.', html: c2 },
            { name: `${bizName} — Growth & Conversion`, badge: 'Conversion Pro', description: 'Conversion-driven architecture tailored for maximum client inquiries.', html: c3 }
          ];
        }

        // If markers were missing, attempt fallback extraction
        const blocks = raw.match(/```(?:html)?\s*([\s\S]*?)\s*```/gi);
        if (blocks && blocks.length >= 3) {
          return blocks.slice(0, 3).map((b, idx) => {
            const h = b.replace(/```(?:html)?/g, '').replace(/```/g, '').trim();
            const names = ['Modern & Bold', 'Clean Minimal', 'Conversion Pro'];
            return {
              name: `${bizName} — ${names[idx]}`,
              badge: `Concept ${idx + 1}`,
              description: `Custom generated concept #${idx + 1} for ${bizName}`,
              html: h
            };
          });
        }

        throw new Error('AI generated content was incomplete. Please retry.');
      } catch (err) {
        console.error('[PuterService] Concept generation error:', err);
        if (this.isQuotaOrCreditError(err)) {
          this.showCreditExhaustedModal();
        }
        throw err;
      }
    }
  }

  // Expose global instance
  window.PuterService = new PuterService();
  window.PUTER_MODELS = PUTER_MODELS;

})(window);
