// Renders the PWA / favicon set from resources/icons/app-icon.svg.  Run: node resources/icons/build-icons.mjs
import sharp from "sharp";
import { readFile, mkdir } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const here = path.dirname(fileURLToPath(import.meta.url));
const out = path.resolve(here, "../../public/assets/icons");
const svg = await readFile(path.join(here, "app-icon.svg"));
await mkdir(out, { recursive: true });

const targets = [
    ["icon-192.png", 192],
    ["icon-512.png", 512],
    ["maskable-512.png", 512],   // full-bleed background, mark inside the safe zone
    ["apple-touch-icon.png", 180],
    ["favicon-32.png", 32],
];
for (const [name, size] of targets) {
    await sharp(svg, { density: 384 }).resize(size, size).png({ compressionLevel: 9 }).toFile(path.join(out, name));
    console.log("wrote", name);
}
