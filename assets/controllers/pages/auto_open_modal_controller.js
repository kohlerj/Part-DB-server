import {Controller} from "@hotwired/stimulus";
import {Modal} from "bootstrap";

/**
 * Automatically opens a Bootstrap modal when this controller connects to the DOM.
 * Usage: add {{ stimulus_controller('pages/auto-open-modal', {modalId: 'my-modal-id'}) }} to any element.
 */
export default class extends Controller
{
    static values = {
        modalId: String,
    };

    connect() {
        const el = document.getElementById(this.modalIdValue);
        if (el) {
            Modal.getOrCreateInstance(el).show();
        }
    }
}
