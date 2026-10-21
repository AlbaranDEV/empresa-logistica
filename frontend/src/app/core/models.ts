export type EstadoPedido = 'pendiente' | 'despachado' | 'entregado';

export const ESTADOS_PEDIDO: readonly EstadoPedido[] = ['pendiente', 'despachado', 'entregado'];

export type Rol = 'admin' | 'operador';

export interface Usuario {
  id: number;
  name: string;
  email: string;
  rol: Rol;
}

export interface Sesion {
  token: string;
  user: Usuario;
}

export interface Cliente {
  id: number;
  nombre: string;
  ciudad: string;
  cantidad_pedidos?: number;
  total_vendido?: number;
}

export interface Pedido {
  id: number;
  cliente_id: number;
  cliente?: Cliente;
  total: number;
  fecha: string;
  estado: EstadoPedido;
  costo_envio: number | null;
}

export interface DatosPedido {
  cliente_id: number;
  total: number;
  fecha: string;
}

export interface Paginado<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

export interface VentasPorCliente {
  data: Cliente[];
  mes: string;
}
