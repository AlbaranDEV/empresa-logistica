import { HttpClient, httpResource } from '@angular/common/http';
import { inject, Service, Signal } from '@angular/core';
import { map, Observable } from 'rxjs';
import { Cliente, DatosPedido, EstadoPedido, Paginado, Pedido, VentasPorCliente } from '../core/models';

export interface FiltroPedidos {
  estado?: EstadoPedido;
  page: number;
}

@Service()
export class PedidosApi {
  private readonly http = inject(HttpClient);

  /** Listado reactivo: se vuelve a consultar cada vez que cambia el filtro. */
  listar(filtro: Signal<FiltroPedidos>) {
    return httpResource<Paginado<Pedido>>(() => {
      const { estado, page } = filtro();
      return { url: '/api/pedidos', params: { page, ...(estado ? { estado } : {}) } };
    });
  }

  obtener(id: number): Observable<Pedido> {
    return this.http.get<{ data: Pedido }>(`/api/pedidos/${id}`).pipe(map((r) => r.data));
  }

  crear(datos: DatosPedido): Observable<Pedido> {
    return this.http.post<{ data: Pedido }>('/api/pedidos', datos).pipe(map((r) => r.data));
  }

  actualizar(id: number, cambios: Partial<DatosPedido> & { estado?: EstadoPedido }): Observable<Pedido> {
    return this.http.patch<{ data: Pedido }>(`/api/pedidos/${id}`, cambios).pipe(map((r) => r.data));
  }

  eliminar(id: number): Observable<void> {
    return this.http.delete<void>(`/api/pedidos/${id}`);
  }

  clientes() {
    return httpResource<{ data: Cliente[] }>(() => '/api/clientes');
  }

  ventasPorCliente() {
    return httpResource<VentasPorCliente>(() => '/api/reportes/ventas-por-cliente');
  }
}
