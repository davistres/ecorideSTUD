import { PurgeCSS } from 'purgecss';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const outputDir = path.join(__dirname, 'public', 'css', 'purged');
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

async function purge() {
  console.log('Démarrage de PurgeCSS...');

  try {
    const result = await new PurgeCSS().purge({
      content: ['./resources/views/**/*.blade.php', './public/js/**/*.js'],
      css: ['./public/css/*.css'],
      safelist: ['active', 'show', 'fade', 'hidden']
    });

    for (const purgeCssResult of result) {
      const originalPath = purgeCssResult.file;
      const fileName = path.basename(originalPath);
      const outputPath = path.join(outputDir, fileName);

      fs.writeFileSync(outputPath, purgeCssResult.css);
      console.log(`Fichier créé: ${outputPath}`);
    }

    console.log('PurgeCSS terminé avec succès!');
  } catch (error) {
    console.error('Erreur lors de l\'exécution de PurgeCSS:', error);
  }
}

purge();
