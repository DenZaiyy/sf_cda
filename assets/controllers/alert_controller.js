import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    connect() {
        //console.log('alert_controller connected!')
        this.setupAutoRemove();
    }

    disconnect() {
        console.log('alert_controller disconnected')
    }

    setupAutoRemove() {
        setTimeout(() => {
            this.element.style.opacity = '0';
            this.element.addEventListener('transitionend', (e) => {
                e.remove();
            })
        }, 3000);
    }
}
