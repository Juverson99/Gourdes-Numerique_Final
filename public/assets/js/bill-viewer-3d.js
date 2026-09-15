/**
 * Bill 3D 360° Viewer — Réalité Virtuelle pour les billets numériques
 * Permet de visualiser les billets en 3D avec rotation 360°
 */

class Bill3DViewer {
  constructor() {
    this.scene = null;
    this.camera = null;
    this.renderer = null;
    this.billMesh = null;
    this.container = null;
    this.isMouseDown = false;
    this.mouseX = 0;
    this.mouseY = 0;
    this.targetRotationX = 0;
    this.targetRotationY = 0;
    this.currentRotationX = 0;
    this.currentRotationY = 0;
    this.autoRotate = true;
    this.autoRotateSpeed = 0.005;
  }

  /**
   * Initialiser la scène 3D
   */
  initScene(container) {
    this.container = container;

    // Dimensions
    const width = container.clientWidth;
    const height = container.clientHeight;

    // Scène
    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0xffffff);
    this.scene.fog = new THREE.Fog(0xffffff, 10, 50);

    // Caméra
    this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    this.camera.position.z = 4;

    // Rendu
    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    this.renderer.setSize(width, height);
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.renderer.shadowMap.enabled = true;
    this.renderer.shadowMap.type = THREE.PCFShadowShadowMap;
    container.appendChild(this.renderer.domElement);

    // Éclairage
    this.setupLighting();

    // Événements souris
    this.setupMouseEvents();

    // Animation
    this.animate();

    // Responsive
    window.addEventListener('resize', () => this.onWindowResize());
  }

  /**
   * Configurer l'éclairage
   */
  setupLighting() {
    // Lumière ambiante
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    this.scene.add(ambientLight);

    // Lumière directionnelle (haut-droite)
    const directionalLight1 = new THREE.DirectionalLight(0xffffff, 1);
    directionalLight1.position.set(5, 5, 3);
    directionalLight1.castShadow = true;
    directionalLight1.shadow.mapSize.width = 2048;
    directionalLight1.shadow.mapSize.height = 2048;
    this.scene.add(directionalLight1);

    // Lumière directionnelle 2 (arrière-gauche pour effet 3D)
    const directionalLight2 = new THREE.DirectionalLight(0x8899ff, 0.4);
    directionalLight2.position.set(-3, 2, -2);
    this.scene.add(directionalLight2);

    // Point light pour relief
    const pointLight = new THREE.PointLight(0xffdd99, 0.3);
    pointLight.position.set(2, 3, 2);
    this.scene.add(pointLight);
  }

  /**
   * Créer un billet 3D à partir d'une image recto, et d'une image verso
   * facultative (si absente, la face arrière reste une couleur unie, comme
   * avant l'ajout du recto/verso).
   */
  createBill(imageUrl, versoUrl = null, width = 3.2, height = 1.6) {
    // Supprimer l'ancien billet s'il existe
    if (this.billMesh) {
      this.scene.remove(this.billMesh);
    }

    // Textures
    const textureLoader = new THREE.TextureLoader();
    textureLoader.load(imageUrl, (texture) => {
      const buildWithVersoMaterial = (versoMaterial) => {
        // Géométrie (une carte plate avec relief)
        const geometry = new THREE.BoxGeometry(width, height, 0.08, 16, 8, 4);

        // Matériaux
        const materials = [
          // Côtés
          new THREE.MeshStandardMaterial({ color: 0x8b7d6b, metalness: 0.3, roughness: 0.5 }),
          new THREE.MeshStandardMaterial({ color: 0x8b7d6b, metalness: 0.3, roughness: 0.5 }),
          // Haut/bas
          new THREE.MeshStandardMaterial({ color: 0xa89968, metalness: 0.2, roughness: 0.6 }),
          new THREE.MeshStandardMaterial({ color: 0xa89968, metalness: 0.2, roughness: 0.6 }),
          // Face avant (image recto)
          new THREE.MeshStandardMaterial({
            map: texture,
            metalness: 0.1,
            roughness: 0.4,
          }),
          // Face arrière (image verso si fournie, sinon couleur unie)
          versoMaterial,
        ];

        this.billMesh = new THREE.Mesh(geometry, materials);
        this.billMesh.castShadow = true;
        this.billMesh.receiveShadow = true;

        // Ajouter du détail avec une map normale
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#888';
        ctx.fillRect(0, 0, 512, 256);

        for (let i = 0; i < 2000; i++) {
          ctx.fillStyle = `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.05)`;
          ctx.fillRect(Math.random() * 512, Math.random() * 256, 2, 2);
        }

        const normalMap = new THREE.CanvasTexture(canvas);
        normalMap.needsUpdate = true;
        materials[4].normalMap = normalMap;
        materials[4].normalScale.set(0.3, 0.3);

        this.scene.add(this.billMesh);
        this.currentRotationX = 0;
        this.currentRotationY = 0;
      };

      if (versoUrl) {
        textureLoader.load(versoUrl, (versoTexture) => {
          buildWithVersoMaterial(new THREE.MeshStandardMaterial({
            map: versoTexture,
            metalness: 0.1,
            roughness: 0.4,
          }));
        }, undefined, () => {
          // Le verso n'a pas pu être chargé : on retombe sur la couleur unie
          buildWithVersoMaterial(new THREE.MeshStandardMaterial({
            color: 0xf5f5f0,
            metalness: 0.15,
            roughness: 0.5,
          }));
        });
      } else {
        buildWithVersoMaterial(new THREE.MeshStandardMaterial({
          color: 0xf5f5f0,
          metalness: 0.15,
          roughness: 0.5,
        }));
      }
    });
  }

  /**
   * Configurer les événements souris
   */
  setupMouseEvents() {
    const canvas = this.renderer.domElement;

    canvas.addEventListener('mousedown', (e) => {
      this.isMouseDown = true;
      this.mouseX = e.clientX;
      this.mouseY = e.clientY;
      this.autoRotate = false;
    });

    canvas.addEventListener('mousemove', (e) => {
      if (this.isMouseDown && this.billMesh) {
        const deltaX = e.clientX - this.mouseX;
        const deltaY = e.clientY - this.mouseY;

        this.targetRotationY += deltaX * 0.01;
        this.targetRotationX += deltaY * 0.01;

        this.mouseX = e.clientX;
        this.mouseY = e.clientY;
      }
    });

    canvas.addEventListener('mouseup', () => {
      this.isMouseDown = false;
      setTimeout(() => {
        if (!this.isMouseDown) {
          this.autoRotate = true;
        }
      }, 300);
    });

    canvas.addEventListener('mouseleave', () => {
      this.isMouseDown = false;
      setTimeout(() => {
        if (!this.isMouseDown) {
          this.autoRotate = true;
        }
      }, 300);
    });

    // Touch pour mobile
    canvas.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        this.isMouseDown = true;
        this.mouseX = e.touches[0].clientX;
        this.mouseY = e.touches[0].clientY;
        this.autoRotate = false;
      }
    });

    canvas.addEventListener('touchmove', (e) => {
      if (this.isMouseDown && this.billMesh && e.touches.length === 1) {
        const deltaX = e.touches[0].clientX - this.mouseX;
        const deltaY = e.touches[0].clientY - this.mouseY;

        this.targetRotationY += deltaX * 0.01;
        this.targetRotationX += deltaY * 0.01;

        this.mouseX = e.touches[0].clientX;
        this.mouseY = e.touches[0].clientY;
      }
    });

    canvas.addEventListener('touchend', () => {
      this.isMouseDown = false;
      setTimeout(() => {
        if (!this.isMouseDown) {
          this.autoRotate = true;
        }
      }, 300);
    });

    // Zoom à la molette
    canvas.addEventListener('wheel', (e) => {
      e.preventDefault();
      const zoomSpeed = 0.1;
      this.camera.position.z += e.deltaY > 0 ? zoomSpeed : -zoomSpeed;
      this.camera.position.z = Math.max(2, Math.min(8, this.camera.position.z));
    });
  }

  /**
   * Animation de rotation fluide
   */
  animate() {
    requestAnimationFrame(() => this.animate());

    if (this.billMesh) {
      // Interpolation lisse
      this.currentRotationX += (this.targetRotationX - this.currentRotationX) * 0.1;
      this.currentRotationY += (this.targetRotationY - this.currentRotationY) * 0.1;

      // Limitation de l'angle X
      this.currentRotationX = Math.max(-Math.PI / 3, Math.min(Math.PI / 3, this.currentRotationX));

      // Rotation automatique
      if (this.autoRotate && !this.isMouseDown) {
        this.currentRotationY += this.autoRotateSpeed;
      }

      this.billMesh.rotation.x = this.currentRotationX;
      this.billMesh.rotation.y = this.currentRotationY;
    }

    this.renderer.render(this.scene, this.camera);
  }

  /**
   * Adapter au redimensionnement de la fenêtre
   */
  onWindowResize() {
    if (!this.container) return;

    const width = this.container.clientWidth;
    const height = this.container.clientHeight;

    this.camera.aspect = width / height;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(width, height);
  }

  /**
   * Nettoyer les ressources
   */
  dispose() {
    if (this.renderer) {
      this.renderer.dispose();
      if (this.renderer.domElement.parentNode) {
        this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
      }
    }
    if (this.scene) {
      this.scene.traverse((child) => {
        if (child.geometry) child.geometry.dispose();
        if (child.material) {
          if (Array.isArray(child.material)) {
            child.material.forEach((m) => m.dispose());
          } else {
            child.material.dispose();
          }
        }
      });
    }
  }
}

// Intégration avec le modal
let bill3DViewer = null;

function openBill3DViewer(billImageUrl, billValue, billVersoUrl = null) {
  // Créer le modal s'il n'existe pas
  let modal = document.getElementById('bill3dModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'bill3dModal';
    modal.innerHTML = `
      <div class="modal-overlay">
        <div class="modal-content bill-3d-modal">
          <div class="modal-header">
            <h3>Vue 3D 360° du billet</h3>
            <button type="button" class="modal-close" id="close3dBtn">&times;</button>
          </div>
          <div class="modal-body">
            <div id="bill3dContainer" style="width:100%;height:500px;"></div>
            <p class="bill-3d-instructions">
              <i class="bi bi-hand-index"></i>
              Cliquez et glissez pour faire tourner le billet • 
              Molette pour zoomer
            </p>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    // Ajouter les styles
    const style = document.createElement('style');
    style.textContent = `
      #bill3dModal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }

      #bill3dModal .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      #bill3dModal .modal-content {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease-out;
      }

      @keyframes slideUp {
        from {
          transform: translateY(30px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }

      #bill3dModal .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      #bill3dModal .modal-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
      }

      #bill3dModal .modal-close {
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      #bill3dModal .modal-close:hover {
        color: #333;
      }

      #bill3dModal .modal-body {
        flex: 1;
        padding: 1.5rem;
        overflow: auto;
      }

      #bill3dContainer {
        border-radius: 8px;
        overflow: hidden;
        background: #f9f9f9;
        cursor: grab;
      }

      #bill3dContainer:active {
        cursor: grabbing;
      }

      .bill-3d-instructions {
        margin-top: 1rem;
        font-size: 0.85rem;
        color: #666;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }

      @media (max-width: 768px) {
        #bill3dModal .modal-content {
          width: 95%;
          max-height: 95vh;
        }

        #bill3dContainer {
          height: 350px !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  // Remplir le conteneur
  const container = document.getElementById('bill3dContainer');
  container.innerHTML = '';

  // Charger Three.js si nécessaire
  if (!window.THREE) {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    script.onload = () => {
      initBill3DViewer(container, billImageUrl, billValue, billVersoUrl);
    };
    document.head.appendChild(script);
  } else {
    initBill3DViewer(container, billImageUrl, billValue, billVersoUrl);
  }

  // Afficher le modal
  modal.style.display = 'flex';

  // Fermer le modal
  document.getElementById('close3dBtn').addEventListener('click', () => {
    modal.style.display = 'none';
    if (bill3DViewer) {
      bill3DViewer.dispose();
      bill3DViewer = null;
    }
  });

  // Fermer au clic sur le overlay
  document.querySelector('.modal-overlay').addEventListener('click', (e) => {
    if (e.target === document.querySelector('.modal-overlay')) {
      modal.style.display = 'none';
      if (bill3DViewer) {
        bill3DViewer.dispose();
        bill3DViewer = null;
      }
    }
  });
}

function initBill3DViewer(container, billImageUrl, billValue, billVersoUrl = null) {
  if (bill3DViewer) {
    bill3DViewer.dispose();
  }

  bill3DViewer = new Bill3DViewer();
  bill3DViewer.initScene(container);
  bill3DViewer.createBill(billImageUrl, billVersoUrl);
}
