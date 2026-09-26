/**
 * ai-admin-generator.js
 * 
 * Bolt.new / Lovable style — lets AI write COMPLETE admin panel code.
 * No templates. AI generates every PHP file, SQL schema, and asset
 * based on the user's actual requirements.
 */

window.AIAdminGenerator = (function () {

  /* ─── The Master Prompt ─────────────────────────────── */
  function buildPrompt(opts) {
    const {
      bizName = 'Business',
      bizType = 'business',
      userRequirements = '',
      siteSummary = '',
      genMode = 'admin',        // admin | database
      existingHtml = ''
    } = opts;

    const dbLine = genMode === 'database'
      ? 'Generate a MySQL schema (CREATE TABLE ...) for every entity. Use PDO with prepared statements in every PHP file.'
      : 'Use a simple JSON file storage engine (data_<entity>.json) — no MySQL required. Still write clean PHP.';

    return `You are an expert senior full-stack PHP developer. Your job is to write COMPLETE, PRODUCTION-READY admin panel code from scratch.

# CONTEXT
Business: "${bizName}" (${bizType})
User's exact requirements:
"""
${userRequirements || 'Generate a sensible admin panel for this business type.'}
"""

# YOUR TASK
Design and write a complete PHP admin panel that satisfies the user's requirements.

# OUTPUT FORMAT
Respond with ONLY a single valid JSON object (no markdown fences, no explanation, no comments outside the JSON). Exact shape:

{
  "site_name": "${bizName}",
  "admin_title": "string — displayed in login page & dashboard",
  "entities": [
    {
      "id": "students",
      "name": "Students",
      "singular": "Student",
      "icon": "👨‍🎓",
      "fields": [
        {
          "name": "student_name",
          "label": "Student Name",
          "type": "text",
          "required": true,
          "in_list": true,
          "options": []
        }
      ]
    }
  ],
  "files": {
    "config.php": "<?php /* full code */ ",
    "includes/db.php": "<?php ...",
    "includes/auth.php": "<?php ...",
    "includes/header.php": "<?php ...",
    "includes/footer.php": "<?php ...",
    "login.php": "<?php ...",
    "logout.php": "<?php ...",
    "index.php": "<?php ... dashboard with card links to each entity ...",
    "students/list.php": "<?php ...",
    "students/add.php": "<?php ...",
    "students/edit.php": "<?php ...",
    "students/delete.php": "<?php ...",
    "students/save.php": "<?php ...",
    "teachers/list.php": "<?php ...",
    "teachers/add.php": "<?php ..."
  },
  "sql": "CREATE TABLE students (...); CREATE TABLE teachers (...);",
  "setup_notes": "Plain-English note for the customer about what was built."
}

# STRICT RULES — READ CAREFULLY

1. **Every single file must be complete, working PHP code.** No placeholders, no "TODO", no "...".

2. **Login system**: \`login.php\` reads credentials from a hash file (\`.auth.json\`) that the publish system writes. It checks \`password_verify(\$pw, \$storedHash)\`. On success: \`\$_SESSION['admin'] = true; header('Location: index.php');\`.

3. **Auth guard**: \`includes/auth.php\` — call \`requireAdmin()\` at the top of every protected page. It should redirect to \`login.php\` if \`empty(\$_SESSION['admin'])\`.

4. **DB layer** (${genMode === 'database' ? 'MySQL' : 'JSON file'}):
   ${genMode === 'database'
      ? '— \`includes/db.php\` uses PDO with config from config.php.\n   — Every query uses prepared statements.\n   — Never concatenate user input into SQL.'
      : '— \`includes/db.php\` provides helpers: load_json(\$entity), save_json(\$entity, \$rows).\n   — Data stored in data_<entity>.json files next to the admin folder.'}

5. **List pages**: Show a table with: search box, all fields marked in_list=true, Edit + Delete buttons per row, and a big "+ Add X" button at the top.

6. **Add / Edit pages**: Render a form with every field. Input type must match the field type (text→text, email→email, date→date, textarea→textarea, select→select with options, file→file upload). Show validation errors inline.

7. **Save pages**: Handle POST. Validate required fields. Insert or update. Redirect to list.php on success.

8. **Delete pages**: Read \`?id=\`, delete the row, redirect to list.

9. **Dashboard** (\`index.php\`): Greeting, "View Live Site" button, and a grid of cards — one per entity, showing the entity icon, name, and a record count. Each card links to that entity's \`list.php\`.

10. **Styling**: Include all CSS inline inside \`includes/header.php\` (a <style> block). Use a **modern dark theme** — background #0a0d14, cards #111622, primary #6366f1, rounded corners, clean typography (system-ui font stack). Buttons with gradient backgrounds.

11. **No external CDNs** (except optionally Google Fonts). Everything must work offline.

12. **Security**: htmlspecialchars() on all output, PDO prepared statements (for MySQL mode), session regeneration on login, no SQL injection, no XSS.

13. **Icons**: Use emoji in the code for entity icons. Keep code ASCII-safe except emojis.

14. **Generate for EVERY entity the user mentions.** If they say "students, teachers, classes, fees, attendance" → generate all 5 (each with list/add/edit/delete/save).

15. **Field names must be snake_case** in JSON but the label is whatever the user-friendly name is.

# IMPORTANT
- Do NOT wrap the JSON in markdown.
- Do NOT add commentary.
- Do NOT abbreviate any file.
- Total files will be large — that's fine.
- If the user gave very specific fields (like "add student with name, class, roll no, parent phone"), use exactly those fields.
- If the user was vague ("school admin"), invent reasonable fields.

Begin your JSON response now:`;
  }

  /* ─── The main generator call ───────────────────────── */
  async function generate(opts, onProgress) {
    const prompt = buildPrompt(opts);
    const progress = typeof onProgress === 'function' ? onProgress : () => {};

    progress({ stage: 'thinking', message: 'AI is analyzing your requirements…' });

    let raw;
    try {
      // Try Puter (primary)
      if (window.puter && window.PuterService) {
        const model = window.PuterService.selectedModel || 'deepseek/deepseek-chat';
        progress({ stage: 'generating', message: `Generating admin panel with ${model.split('/').pop()}…` });
        raw = await window.PuterService.chat(prompt, { temperature: 0.3, max_tokens: 16000 });
      } else if (window.puter) {
        raw = await window.puter.ai.chat(prompt);
      } else {
        throw new Error('Puter.js not available');
      }
    } catch (err) {
      // Fallback: try backend Gemini
      progress({ stage: 'fallback', message: 'Trying backup AI…' });
      raw = await callBackendFallback(prompt);
    }

    progress({ stage: 'parsing', message: 'Parsing generated code…' });
    const parsed = parseStructuredJson(raw);
    if (!parsed || !parsed.files) {
      throw new Error('AI did not return valid code structure. Try again or rephrase requirements.');
    }

    progress({ stage: 'done', message: `✅ Generated ${Object.keys(parsed.files).length} files` });
    return parsed;
  }

  /* ─── Very robust JSON extractor ────────────────────── */
  function parseStructuredJson(raw) {
    if (!raw) return null;
    let text = typeof raw === 'string' ? raw : (raw.message?.content || raw.text || JSON.stringify(raw));

    // Strip markdown fences
    text = text.replace(/^```(?:json)?\s*/i, '').replace(/```\s*$/i, '').trim();

    // Direct parse
    try { return JSON.parse(text); } catch (_) {}

    // Find first { ... matching }
    const start = text.indexOf('{');
    if (start === -1) return null;
    let depth = 0, inStr = false, esc = false, end = -1;
    for (let i = start; i < text.length; i++) {
      const c = text[i];
      if (inStr) {
        if (esc) { esc = false; continue; }
        if (c === '\\') { esc = true; continue; }
        if (c === '"') inStr = false;
        continue;
      }
      if (c === '"') { inStr = true; continue; }
      if (c === '{') depth++;
      else if (c === '}') {
        depth--;
        if (depth === 0) { end = i; break; }
      }
    }
    if (end === -1) return null;
    try { return JSON.parse(text.slice(start, end + 1)); } catch (e) {
      console.warn('AIAdminGenerator: JSON parse failed', e);
      return null;
    }
  }

  /* ─── Backend Gemini fallback ───────────────────────── */
  async function callBackendFallback(prompt) {
    const res = await fetch('api/generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'raw_chat',
        api_key: localStorage.getItem('gemini_api_key') || '',
        prompt
      })
    });
    if (!res.ok) throw new Error('Backend AI request failed');
    const j = await res.json();
    if (!j.success) throw new Error(j.error || 'Backend error');
    return j.text || j.response || '';
  }

  /* ─── Fix a specific file with AI ───────────────────── */
  async function fixFile(filePath, currentCode, issue, fullContext) {
    const prompt = `You are fixing a single PHP file. Return ONLY the corrected file content — no markdown, no explanation.

FILE PATH: ${filePath}
ISSUE: ${issue}

CONTEXT (other files in the project, for reference):
${JSON.stringify(fullContext?.files ? Object.keys(fullContext.files) : [], null, 2)}

CURRENT CODE:
${currentCode}

Return the fixed file now:`;

    let fixed;
    if (window.puter) fixed = await window.puter.ai.chat(prompt);
    else fixed = await callBackendFallback(prompt);

    // Strip fences
    fixed = String(fixed).replace(/^```(?:php|html|js)?\s*/i, '').replace(/```\s*$/i, '').trim();
    return fixed;
  }

  /* ─── Preview helper: build file tree ───────────────── */
  function buildFileTree(files) {
    const root = { name: '', path: '', children: {}, isFile: false, content: null };
    Object.keys(files).forEach(path => {
      const parts = path.split('/');
      let node = root;
      parts.forEach((part, i) => {
        const isLast = i === parts.length - 1;
        if (!node.children[part]) {
          node.children[part] = {
            name: part,
            path: parts.slice(0, i + 1).join('/'),
            children: {},
            isFile: isLast,
            content: isLast ? files[path] : null
          };
        }
        node = node.children[part];
      });
    });
    return root;
  }

  /* ─── Public API ────────────────────────────────────── */
  return {
    generate,
    fixFile,
    buildFileTree,
    parseStructuredJson
  };
})();