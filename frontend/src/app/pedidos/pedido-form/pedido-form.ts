import { CurrencyPipe } from '@angular/common';
import { Component, computed, inject, input, numberAttribute, OnInit, signal } from '@angular/core';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { Auth } from '../../core/auth';
import { mensajeDeError } from '../../core/errores';
import { DatosPedido, Pedido } from '../../core/models';
import { Icon } from '../../shared/icon';
import { PedidosApi } from '../pedidos-api';

/**
 * Formulario para crear (/pedidos/nuevo) o editar (/pedidos/:id/editar) un pedido.
 * El estado se cambia desde el listado.
 */
@Component({
  imports: [ReactiveFormsModule, RouterLink, CurrencyPipe, Icon],
  selector: 'app-pedido-form',
  styleUrl: './pedido-form.scss',
  templateUrl: './pedido-form.html',
})
export class PedidoForm implements OnInit {
  private readonly api = inject(PedidosApi);
  private readonly auth = inject(Auth);
  private readonly router = inject(Router);

  readonly id = input(undefined, { transform: (valor: unknown) => (valor ? numberAttribute(valor) : undefined) });

  protected readonly clientes = this.api.clientes();
  protected readonly pedido = signal<Pedido | null>(null);
  protected readonly editando = computed(() => this.id() !== undefined);
  protected readonly bloqueado = computed(() => this.pedido()?.estado === 'entregado' && !this.auth.esAdmin());
  protected readonly enviando = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly form = inject(NonNullableFormBuilder).group({
    cliente_id: [0, Validators.min(1)],
    total: [null as number | null, [Validators.required, Validators.min(0.01)]],
    fecha: [hoy(), Validators.required],
  });

  ngOnInit(): void {
    const id = this.id();
    if (id === undefined) {
      return;
    }

    this.api.obtener(id).subscribe({
      next: (pedido) => {
        this.pedido.set(pedido);
        this.form.setValue({ cliente_id: pedido.cliente_id, total: pedido.total, fecha: pedido.fecha });
        if (this.bloqueado()) {
          this.form.disable();
        }
      },
      error: (error) => this.error.set(mensajeDeError(error)),
    });
  }

  protected guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    // El validador "required" garantiza que total no es null en este punto.
    const datos = this.form.getRawValue() as DatosPedido;
    const id = this.id();
    const operacion: Observable<Pedido> = id === undefined ? this.api.crear(datos) : this.api.actualizar(id, datos);

    this.enviando.set(true);
    this.error.set(null);

    operacion.subscribe({
      next: () => this.router.navigate(['/pedidos']),
      error: (error) => {
        this.error.set(mensajeDeError(error));
        this.enviando.set(false);
      },
    });
  }
}

function hoy(): string {
  const fecha = new Date();
  fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());
  return fecha.toISOString().slice(0, 10);
}
