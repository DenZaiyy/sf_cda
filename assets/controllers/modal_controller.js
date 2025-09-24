import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["modal", "backdrop", "panel", "title", "body", "footer", "turboFrame"]
    static values = { 
        closable: Boolean,
        backdrop: Boolean,
        id: String
    }

    connect() {
        this.isOpen = false;
        
        // Listen for escape key
        this.escapeHandler = this.handleEscape.bind(this);
        
        // Prevent body scroll when modal is open
        this.originalBodyOverflow = document.body.style.overflow;
    }

    disconnect() {
        document.removeEventListener('keydown', this.escapeHandler);
        // Restore body scroll
        document.body.style.overflow = this.originalBodyOverflow;
    }

    openModal(event) {
        if (event) event.preventDefault();
        
        if (this.isOpen) return;
        
        this.isOpen = true;
        
        // Show modal
        if (this.hasModalTarget) {
            this.modalTarget.classList.remove('hidden');
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Add escape key listener
        document.addEventListener('keydown', this.escapeHandler);
        
        // Trigger animations after a small delay to ensure the element is visible
        requestAnimationFrame(() => {
            if (this.hasBackdropTarget) {
                this.backdropTarget.classList.remove('opacity-0');
                this.backdropTarget.classList.add('opacity-100');
            }
            
            if (this.hasPanelTarget) {
                this.panelTarget.classList.remove('scale-95', 'opacity-0');
                this.panelTarget.classList.add('scale-100', 'opacity-100');
            }
            
            // Load Turbo Frame content when modal opens
            if (this.hasTurboFrameTarget) {
                this.loadTurboFrame();
            }
        });
    }

    closeModal(event) {
        if (event) event.preventDefault();
        
        if (!this.isOpen) return;
        
        // Don't close if closable is false
        if (!this.closableValue) return;
        
        this.isOpen = false;
        
        // Trigger exit animations
        this.backdropTarget.classList.remove('opacity-100');
        this.backdropTarget.classList.add('opacity-0');
        
        this.panelTarget.classList.remove('scale-100', 'opacity-100');
        this.panelTarget.classList.add('scale-95', 'opacity-0');
        
        // Hide modal after animation completes
        setTimeout(() => {
            if (this.hasModalTarget) {
                this.modalTarget.classList.add('hidden');
            }
            document.body.style.overflow = this.originalBodyOverflow;
            document.removeEventListener('keydown', this.escapeHandler);
        }, 300); // Match the CSS transition duration
    }

    handleEscape(event) {
        if (event.key === 'Escape' && this.isOpen && this.closableValue) {
            this.closeModal();
        }
    }

    // Method to update modal content dynamically
    updateContent(title = null, body = null, footer = null) {
        if (title !== null && this.hasTitleTarget) {
            this.titleTarget.textContent = title;
        }
        
        if (body !== null && this.hasBodyTarget) {
            this.bodyTarget.innerHTML = body;
        }
        
        if (footer !== null && this.hasFooterTarget) {
            this.footerTarget.innerHTML = footer;
        }
    }

    // Load Turbo Frame content
    loadTurboFrame() {
        const src = this.turboFrameTarget.dataset.src;
        if (src && !this.turboFrameTarget.hasAttribute('src')) {
            this.turboFrameTarget.setAttribute('src', src);
        }
    }

    // Handle success messages and auto-close modal
    handleSuccess(event) {
        // Auto-close modal after 2 seconds when success message is shown
        setTimeout(() => {
            this.closeModal();
        }, 2000);
    }

    // Getter for current state
    get isModalOpen() {
        return this.isOpen;
    }
}
