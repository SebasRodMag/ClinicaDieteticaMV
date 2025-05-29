import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
    selector: 'app-modal-create',
    imports: [
      CommonModule
    ],
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