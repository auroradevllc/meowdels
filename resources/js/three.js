import * as THREE from 'three';
import { STLLoader } from 'three/addons/loaders/STLLoader';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader';
import { OrbitControls } from 'three/addons/controls/OrbitControls';

import * as fflate from 'three/examples/jsm/libs/fflate.module.js';

async function check3MFExtensions(buffer) {
    const zip = fflate.unzipSync(new Uint8Array(buffer));

    // 3MF models are stored in 3D/3dmodel.model
    const modelData = zip['3D/3dmodel.model'];
    if (modelData) {
        const decoder = new TextDecoder();
        const xmlString = decoder.decode(modelData);

        // Quick regex check for required extensions
        const match = xmlString.match(/requiredextensions="([^"]+)"/);
        if (match) {
            console.log("Required Extensions found:", match[1]);
            return match[1].split(' ');
        }
    }
    return [];
}

async function getCleaned3MFBuffer(buffer) {
    // 1. Unzip the file
    const zip = fflate.unzipSync(new Uint8Array(buffer));
    const modelPath = '3D/3dmodel.model';

    if (!zip[modelPath]) {
        throw new Error("Invalid 3MF: Could not find 3D/3dmodel.model");
    }

    // 2. Decode the XML to a string
    const decoder = new TextDecoder();
    let xmlString = decoder.decode(zip[modelPath]);

    // 3. Strip 'requiredextensions' and 'xmlns' for production/beams
    // This regex targets the attributes specifically
    xmlString = xmlString.replace(/requiredextensions="[^"]*"/g, '');

    // Optional: Remove the specific namespace declarations if they cause XML heartaches
    //xmlString = xmlString.replace(/xmlns:[a-z]+="[^"]*"/g, '');

    // 4. Update the file in our virtual zip object
    const encoder = new TextEncoder();
    zip[modelPath] = encoder.encode(xmlString);

    // 5. Re-zip into a new ArrayBuffer
    const zippedData = fflate.zipSync(zip);
    return zippedData.buffer;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('stlPreviewer', (config) => {
        // Keep these non-reactive
        let scene, renderer, camera, controls, animationId, mesh, grid;

        return {
            url: config.url,
            type: config.type,
            isLoading: false,

            init() {
                if (this.type === 'stl') {
                    this.$nextTick(() => this.initThreeJs());
                }
                this.$watch('url', () => {
                    this.cleanup();
                    this.initThreeJs();
                });
            },

            async initThreeJs() {
                const container = this.$refs.canvasContainer;
                if (!container) return;

                const ext = this.url.substring(this.url.lastIndexOf('.')+1);

                const response = await fetch(this.url);

                if (!response.ok)
                    throw new Error(`Failed to fetch: ${response.status}`);

                let buffer = await response.arrayBuffer();

                if (ext === '3mf') {
                    const extensions = await check3MFExtensions(buffer);
                    console.log("Detected extensions:", extensions);

                    if (extensions.includes('p')) {
                        buffer = await getCleaned3MFBuffer(buffer);
                    }
                }

                this.isLoading = true;

                // 1. Scene & Camera
                scene = new THREE.Scene();
                scene.background = new THREE.Color(0x171717); // Neutral 950

                camera = new THREE.PerspectiveCamera(45, container.offsetWidth / container.offsetHeight, 0.1, 2000);

                renderer = new THREE.WebGLRenderer({ antialias: true });
                renderer.setSize(container.offsetWidth, container.offsetHeight);
                renderer.setPixelRatio(window.devicePixelRatio);
                container.appendChild(renderer.domElement);

                controls = new OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;
                controls.dampingFactor = 0.05;

                scene.add(new THREE.AmbientLight(0xffffff, 0.8));
                const sun = new THREE.DirectionalLight(0xffffff, 1.2);
                sun.position.set(100, 200, 100);
                scene.add(sun);

                let loader;

                switch (ext) {
                    case 'stl':
                        loader = new STLLoader();
                        break;
                    case '3mf':
                        loader = new ThreeMFLoader();
                        break;
                }

                const geometry = loader.parse(buffer);

                // Standard material
                const material = new THREE.MeshStandardMaterial({
                    color: 0x0ea5e9,
                    metalness: 0.6,
                    roughness: 0.4
                });

                mesh = new THREE.Mesh(geometry, material);

                // Ensure normals are correct for smooth lighting
                geometry.computeVertexNormals();
                geometry.center();

                // Calculate dimensions
                geometry.computeBoundingBox();
                const size = new THREE.Vector3();
                geometry.boundingBox.getSize(size);

                // Position bottom of model at Y=0
                mesh.position.y = size.y / 2;

                scene.add(mesh);

                // Adjust camera to fit the specific model size
                const maxDim = Math.max(size.x, size.y, size.z);
                const fov = camera.fov * (Math.PI / 180);
                let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2));

                // Zoom out slightly more for perspective (1.5x)
                camera.position.set(cameraZ, cameraZ, cameraZ);
                camera.lookAt(0, size.y / 2, 0);

                if (controls) {
                    controls.target.set(0, size.y / 2, 0);
                    controls.update();
                }

                this.isLoading = false;
                this.startAnimation();
            },

            startAnimation() {
                const animate = () => {
                    animationId = requestAnimationFrame(animate);
                    if (controls) controls.update(); // Required for damping
                    renderer.render(scene, camera);
                };
                animate();
            },

            cleanup() {
                if (animationId) cancelAnimationFrame(animationId);
                if (renderer) {
                    renderer.dispose();
                    renderer.domElement.remove();
                }
                if (controls) controls.dispose();
            }
        };
    });
});
