import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgSelectComponent } from '@ng-select/ng-select';

@Component({
    selector: 'app-modal-create',
    imports: [CommonModule, FormsModule],
    templateUrl: './modal-create.component.html',
    styleUrl: './modal-create.component.css',
})
export class ModalCreateComponent {
    isVisible = false;

    @Output() closed = new EventEmitter<void>();

    open() {
        this.isVisible = true;
    }

    close() {
        this.isVisible = false;
        this.closed.emit();
    }
}
