import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { Paginado, Pedido, Sesion } from '../../core/models';
import { PedidosList } from './pedidos-list';

const cliente = { id: 1, nombre: 'Café del Quindío', ciudad: 'Armenia' };

const respuesta: Paginado<Pedido> = {
  data: [
    { id: 1, cliente_id: 1, cliente, total: 1000, fecha: '2026-10-05', estado: 'pendiente', costo_envio: null },
    { id: 2, cliente_id: 1, cliente, total: 2000, fecha: '2026-10-06', estado: 'entregado', costo_envio: 18500 },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
};

function iniciarComo(rol: Sesion['user']['rol']): void {
  const sesion: Sesion = { token: 't', user: { id: 1, name: 'Usuario', email: 'u@logistica.test', rol } };
  localStorage.setItem('logistica.sesion', JSON.stringify(sesion));
}

describe('PedidosList', () => {
  let http: HttpTestingController;

  function crear(estado?: string) {
    TestBed.configureTestingModule({
      imports: [PedidosList],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    });
    http = TestBed.inject(HttpTestingController);

    const fixture = TestBed.createComponent(PedidosList);
    if (estado) {
      fixture.componentRef.setInput('estado', estado);
    }
    return fixture;
  }

  beforeEach(() => localStorage.clear());
  afterEach(() => http.verify());

  it('consulta la API con el estado del filtro', async () => {
    iniciarComo('operador');
    const fixture = crear('pendiente');
    TestBed.tick();

    const peticion = http.expectOne((req) => req.url === '/api/pedidos');
    expect(peticion.request.params.get('estado')).toBe('pendiente');
    expect(peticion.request.params.get('page')).toBe('1');
    peticion.flush(respuesta);
  });

  it('ignora un estado de filtro desconocido', async () => {
    iniciarComo('operador');
    const fixture = crear('cancelado');
    TestBed.tick();

    const peticion = http.expectOne((req) => req.url === '/api/pedidos');
    expect(peticion.request.params.has('estado')).toBe(false);
    peticion.flush(respuesta);
  });

  it('muestra los pedidos y bloquea los entregados para un operador', async () => {
    iniciarComo('operador');
    const fixture = crear();
    TestBed.tick();
    http.expectOne((req) => req.url === '/api/pedidos').flush(respuesta);
    await fixture.whenStable();

    const filas = (fixture.nativeElement as HTMLElement).querySelectorAll('tbody tr');
    expect(filas.length).toBe(2);
    expect(filas[0].textContent).toContain('Café del Quindío');
    expect(filas[0].querySelector('select')!.disabled).toBe(false);
    expect(filas[1].querySelector('select')!.disabled).toBe(true);
    expect(filas[1].textContent).toContain('Bloqueado');
  });

  it('permite al administrador modificar pedidos entregados', async () => {
    iniciarComo('admin');
    const fixture = crear();
    TestBed.tick();
    http.expectOne((req) => req.url === '/api/pedidos').flush(respuesta);
    await fixture.whenStable();

    const entregado = (fixture.nativeElement as HTMLElement).querySelectorAll('tbody tr')[1];
    expect(entregado.querySelector('select')!.disabled).toBe(false);
    expect(entregado.textContent).not.toContain('Bloqueado');
  });

  it('cambia el estado de un pedido y recarga el listado', async () => {
    iniciarComo('operador');
    const fixture = crear();
    TestBed.tick();
    http.expectOne((req) => req.url === '/api/pedidos').flush(respuesta);
    await fixture.whenStable();

    const selector = (fixture.nativeElement as HTMLElement).querySelector<HTMLSelectElement>('tbody tr select')!;
    selector.value = 'despachado';
    selector.dispatchEvent(new Event('change'));

    const cambio = http.expectOne('/api/pedidos/1');
    expect(cambio.request.method).toBe('PATCH');
    expect(cambio.request.body).toEqual({ estado: 'despachado' });
    cambio.flush({ data: { ...respuesta.data[0], estado: 'despachado', costo_envio: 18500 } });

    TestBed.tick();
    http.expectOne((req) => req.url === '/api/pedidos').flush(respuesta);
  });
});
