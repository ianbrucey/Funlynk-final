import fs from 'fs-extra';
import path from 'path';

const source = path.resolve(process.cwd(), 'storage/app/public');
const destination = path.resolve(process.cwd(), 'public/storage');

if (fs.existsSync(destination)) {
    fs.removeSync(destination);
    console.log(`Removed existing directory: ${destination}`);
}

console.log(`Copying storage files from ${source} to ${destination}`);
fs.copySync(source, destination);
console.log('Storage files copied successfully.');
