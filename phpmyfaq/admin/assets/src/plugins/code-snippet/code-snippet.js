import { Jodit } from 'jodit';
import codeSnippet from '../code-snippet/code-snippet.svg.js';

Jodit.modules.Icon.set('codeSnippet', codeSnippet);

Jodit.plugins.add('codeSnippet', (editor) => {
  // Register the button
  editor.registerButton({
    name: 'codeSnippet',
    group: 'insert',
    icon: 'codeSnippet',
    text: 'Insert Source Code Snippet',
  });

  // Register the command
  editor.registerCommand('codeSnippet', () => {
    const dialog = editor.dlg({ closeOnClickOverlay: true });

    const content = `<form class="row m-4">
      <div class="col-12 mb-2">
        <label class="visually-hidden" for="programming-language">Programming language</label>
        <select class="form-select" id="programming-language" name="programming-language">
          <option value="plaintext">Plain Text</option>
          <option value="bash">Bash</option>
          <option value="c">C</option>
          <option value="cpp">C++</option>
          <option value="css">CSS</option>
          <option value="html">HTML</option>
          <option value="java">Java</option>
          <option value="javascript">JavaScript</option>
          <option value="json">JSON</option>
          <option value="php">PHP</option>
          <option value="python">Python</option>
          <option value="ruby">Ruby</option>
          <option value="sql">SQL</option>
          <option value="typescript">TypeScript</option>
          <option value="xml">XML</option>
        </select>
      </div>
      <div class="col-12 mb-2">
        <label class="visually-hidden" for="code">Source code</label>
        <textarea class="form-control" id="code" rows="15" placeholder="Paste your source code snippet here"></textarea>
      </div>
      <div class="col-12">
        <button type="button" class="btn btn-primary text-end" id="add-code-snippet-button">
          Add source code snippet
        </button>
      </div>
    </form>`;

    dialog
      .setMod('theme', editor.o.theme)
      .setHeader('Insert Source Code Snippet')
      .setContent(content)
      .setSize(Math.min(900, screen.width), Math.min(640, screen.width));

    dialog.open();

    const addCodeSnippetButton = document.getElementById('add-code-snippet-button');
    const language = document.getElementById('programming-language');
    const code = document.getElementById('code');

    const encodeHTML = (str) => {
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    addCodeSnippetButton.addEventListener('click', () => {
      const selectedLanguage = language.value;
      const selectedCode = code.value;
      const codeSnippet = `<pre><code class="language-${selectedLanguage}">${encodeHTML(selectedCode)}</code></pre>`;
      editor.selection.insertHTML(codeSnippet);
      dialog.close();
    });
  });
});
