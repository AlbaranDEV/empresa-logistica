import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, inject, input, numberAttribute, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { finalize, Observable } from 'rxjs';
import { Auth } from '../../core/auth';
import { mensajeDeError } from '../../core/errores';
import { ESTADOS_PEDIDO, EstadoPedido, Pedido } from '../../core/models';
import { FiltroPedidos, PedidosApi } from '../pedidos-api';

@Component({
  imports: [RouterLink, CurrencyPipe, DatePipe],
  selector: 'app-pedidos-list',
  styleUrl: './pedidos-list.scss',
  templateUrl: './pedidos-list.html',
})
export class PedidosList {
  private readonly api = inject(PedidosApi);
  private readonly router = inject(Router);
  private readonly auth = inject(Auth);

  // Parámetros de la URL (?estado=pendiente&page=2), enlazados con withComponentInputBinding.
  readonly estado = input<string>();
  readonly page = input(1, { transform: (valor: unknown) => numberAttribute(valor, 1) });

  protected readonly estados = ESTADOS_PEDIDO;
  protected readonly estadoActual = computed(() => {
    const estado = this.estado() as EstadoPedido;
    return ESTADOS_PEDIDO.includes(estado) ? estado : undefined;
  });
  private readonly filtro = computed<FiltroPedidos>(() => ({ estado: this.estadoActual(), page: this.page() }));

  protected readonly pedidos = this.api.listar(this.filtro);
  protected readonly procesando = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);

  /** Solo un administrador puede modificar pedidos entregados (la API también lo valida). */
  protected bloqueado(pedido: Pedido): boolean {
    return pedido.estado === 'entregado' && !this.auth.esAdmin();
  }

  protected filtrar(estado: string): void {
    this.navegar({ estado: estado || null, page: null });
  }

  protected irAPagina(page: number): void {
    this.navegar({ page });
  }

  protected cambiarEstado(pedido: Pedido, selector: HTMLSelectElement): void {
    const estado = selector.value as EstadoPedido;
    this.ejecutar(pedido, this.api.actualizar(pedido.id, { estado }), () => (selector.value = pedido.estado));
  }

  protected eliminar(pedido: Pedido): void {
    if (confirm(`¿Eliminar el pedido #${pedido.id}?`)) {
      this.ejecutar(pedido, this.api.eliminar(pedido.id));
    }
  }

  private ejecutar(pedido: Pedido, operacion: Observable<unknown>, alFallar?: () => void): void {
    this.procesando.set(pedido.id);
    this.error.set(null);

    operacion
      .pipe(
        finalize(() => {
          this.procesando.set(null);
          this.pedidos.reload();
        }),
      )
      .subscribe({
        error: (error) => {
          this.error.set(mensajeDeError(error));
          alFallar?.();
        },
      });
  }

  private navegar(queryParams: Record<string, string | number | null>): void {
    this.router.navigate([], { queryParams, queryParamsHandling: 'merge' });
  }
}
