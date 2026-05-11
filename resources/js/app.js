import JSZip from 'jszip';

// Make it globally accessible
window.JSZip = JSZip;

import { renderPreview } from './renderer';

window.renderPreview = renderPreview;
