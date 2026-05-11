import * as THREE from 'three';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';

/**
 * Renders an STL or 3MF file with "Auto-Bottom" orientation and OpenSCAD styling.
 */
export async function renderPreview(file, size = 1024) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = async (e) => {
            try {
                const scene = new THREE.Scene();
                scene.background = null;

                // 1. Initialize Loader based on extension
                const is3MF = file.name.toLowerCase().endsWith('.3mf');
                const loader = is3MF ? new ThreeMFLoader() : new STLLoader();

                let geometry;
                if (is3MF) {
                    // 3MF loader returns a Group, we extract the first geometry found
                    const group = loader.parse(e.target.result);
                    group.traverse(child => {
                        if (child.isMesh) geometry = child.geometry.clone();
                    });
                } else {
                    geometry = loader.parse(e.target.result);
                }

                if (!geometry) throw new Error("Could not extract geometry");

                // 2. Auto-Orient: Find largest flat face and make it the "bottom"
                const bestNormal = getLargestFaceNormal(geometry);
                const targetUp = new THREE.Vector3(0, 1, 0);
                const quaternion = new THREE.Quaternion().setFromUnitVectors(bestNormal, targetUp);
                geometry.applyQuaternion(quaternion);

                // 3. Center and Calculate Size
                geometry.center();
                geometry.computeBoundingBox();
                geometry.computeBoundingSphere();
                const radius = geometry.boundingSphere.radius;

                const material = new THREE.MeshPhongMaterial({
                    color: 0xffffff,
                    flatShading: true,
                    shininess: 30
                });
                const mesh = new THREE.Mesh(geometry, material);
                scene.add(mesh);

                // Add Edges (Wireframe look)
                const edges = new THREE.EdgesGeometry(geometry, 20); // 20 degree threshold
                const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x222222 }));
                mesh.add(line);

                // 5. Setup Orthographic Camera (CAD Style)
                const aspect = 1;
                const zoomPadding = 1.25;
                const d = radius * zoomPadding;

                const camera = new THREE.OrthographicCamera(-d * aspect, d * aspect, d, -d, 1, 5000);

                // Position at Isometric Angle
                const camDist = radius * 2;
                camera.position.set(camDist, camDist, camDist);
                camera.lookAt(0, 0, 0);

                // 6. Lights
                const ambLight = new THREE.AmbientLight(0xffffff, 0.8);
                scene.add(ambLight);
                const dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
                dirLight.position.set(1, 1, 1);
                scene.add(dirLight);

                // 8. Final Render
                const renderer = new THREE.WebGLRenderer({
                    antialias: true,
                    preserveDrawingBuffer: true,
                    alpha: true,
                });
                renderer.setPixelRatio(window.devicePixelRatio);
                renderer.setSize(size, size);
                renderer.render(scene, camera);

                // 9. Return Blob
                renderer.domElement.toBlob((blob) => {
                    // Memory Cleanup
                    renderer.dispose();
                    geometry.dispose();
                    material.dispose();

                    resolve(blob);
                }, 'image/png');

            } catch (err) {
                reject(err);
            }
        };

        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

/**
 * Helper: Analyzes face normals and areas to find the most dominant "flat" direction.
 */
function getLargestFaceNormal(geometry) {
    const normals = {};
    const pos = geometry.attributes.position;
    const vA = new THREE.Vector3(), vB = new THREE.Vector3(), vC = new THREE.Vector3();
    const cb = new THREE.Vector3(), ab = new THREE.Vector3();

    for (let i = 0; i < pos.count; i += 3) {
        vA.fromBufferAttribute(pos, i);
        vB.fromBufferAttribute(pos, i + 1);
        vC.fromBufferAttribute(pos, i + 2);

        cb.subVectors(vC, vB);
        ab.subVectors(vA, vB);
        cb.cross(ab);
        const area = cb.length() * 0.5;

        const faceNormal = cb.normalize();
        // Precision rounding to group similar normals
        const key = `${faceNormal.x.toFixed(2)},${faceNormal.y.toFixed(2)},${faceNormal.z.toFixed(2)}`;
        normals[key] = (normals[key] || 0) + area;
    }

    let maxArea = 0;
    let bestNormal = new THREE.Vector3(0, 0, 1);
    for (const [key, area] of Object.entries(normals)) {
        if (area > maxArea) {
            maxArea = area;
            const parts = key.split(',').map(Number);
            bestNormal.set(parts[0], parts[1], parts[2]);
        }
    }
    return bestNormal;
}
