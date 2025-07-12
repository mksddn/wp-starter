const { HtmlValidate } = require('html-validate');
const fs = require('fs');
const path = require('path');

const htmlvalidate = new HtmlValidate({
  extends: ['html-validate:recommended'],
  rules: {
    'parser-error': 'off',
    'no-trailing-whitespace': 'off',
    'no-inline-style': 'off',
    'void-style': 'off',
    'doctype-style': 'off',
    'attribute-allowed-values': 'off',
    'prefer-button': 'off',
    'valid-id': 'off'
  }
});

function stripPHP(content) {
  // Remove all <?php ... ?> blocks
  return content.replace(/<\?php[\s\S]*?\?>/g, '');
}

function getAllPhpFiles(dir) {
  let results = [];
  fs.readdirSync(dir).forEach(function(file) {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat && stat.isDirectory()) {
      results = results.concat(getAllPhpFiles(filePath));
    } else if (file.endsWith('.php')) {
      results.push(filePath);
    }
  });
  return results;
}

const targets = [
  'wp-content/themes/wp-theme',
  'wp-content/mu-plugins',
];

let hasErrors = false;

(async () => {
  for (const base of targets) {
    if (!fs.existsSync(base)) continue;
    const files = getAllPhpFiles(base);
    for (const file of files) {
      const raw = fs.readFileSync(file, 'utf8');
      const html = stripPHP(raw);
      const report = await htmlvalidate.validateString(html);
      let fileHasErrors = false;
      let output = '';
      for (const res of report.results) {
        for (const msg of res.messages) {
          if (msg.ruleId === 'parser-error') continue;
          // Filter Stray end tag errors (close-order) only for unique tags
          if (
            msg.ruleId === 'close-order' &&
            msg.message &&
            msg.message.startsWith("Stray end tag '")
          ) {
            const uniqueTags = ['html', 'head', 'body', 'title'];
            const match = msg.message.match(/Stray end tag '<\/?([a-zA-Z0-9\-]+)>'/);
            if (match && uniqueTags.includes(match[1])) continue;
          }
          // Filter Unclosed element errors only for unique tags
          if (
            msg.ruleId === 'close-order' &&
            msg.message &&
            msg.message.startsWith("Unclosed element '")
          ) {
            const uniqueTags = ['html', 'head', 'body', 'title'];
            const match = msg.message.match(/Unclosed element '<\/?([a-zA-Z0-9\-]+)>'/);
            if (match && uniqueTags.includes(match[1])) continue;
          }
          // Filter element-required-attributes errors only for <html>
          if (
            msg.ruleId === 'element-required-attributes' &&
            msg.message &&
            msg.message.includes('<html>')
          ) continue;
          // Filter element-required-content errors only for <head>
          if (
            msg.ruleId === 'element-required-content' &&
            msg.message &&
            msg.message.includes('<head>')
          ) continue;
          fileHasErrors = true;
          // Get the line with the error from the cleaned HTML
          let errorLine = '';
          if (msg.line && typeof msg.line === 'number') {
            const htmlLines = html.split(/\r?\n/);
            if (msg.line - 1 < htmlLines.length) {
              errorLine = htmlLines[msg.line - 1].trim();
            }
          }
          output += `  [${msg.ruleId}] ${msg.message}\n    > ${errorLine}\n`;
        }
      }
      if (fileHasErrors) {
        hasErrors = true;
        console.log(`\nError in file: ${file}`);
        process.stdout.write(output);
        console.log('----------------------');
      }
    }
  }
  if (hasErrors) {
    process.exit(1);
  } else {
    console.log('All HTML fragments are valid!');
  }
})(); 