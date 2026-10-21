import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { Auth } from './auth';
import { authInterceptor } from './auth-interceptor';
import { Sesion } from './models';

const sesion: Sesion = {
  token: '1|abc',
  user: { id: 1, name: 'Admin', email: 'admin@logistica.test', rol: 'admin' },
};

describe('Auth', () => {
  let auth: Auth;
  let http: HttpTestingController;

  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    auth = TestBed.inject(Auth);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  function iniciarSesion(): void {
    auth.login('admin@logistica.test', 'password').subscribe();
    http.expectOne('/api/login').flush(sesion);
  }

  it('guarda la sesión al iniciar sesión', () => {
    iniciarSesion();

    expect(auth.autenticado()).toBe(true);
    expect(auth.esAdmin()).toBe(true);
    expect(JSON.parse(localStorage.getItem('logistica.sesion')!)).toEqual(sesion);
  });

  it('adjunta el token a las peticiones de la API', () => {
    iniciarSesion();

    TestBed.inject(HttpClient).get('/api/pedidos').subscribe();
    const peticion = http.expectOne('/api/pedidos');

    expect(peticion.request.headers.get('Authorization')).toBe('Bearer 1|abc');
    expect(peticion.request.headers.get('Accept')).toBe('application/json');
    peticion.flush({});
  });

  it('cierra la sesión y redirige al login cuando la API responde 401', () => {
    const navegar = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    iniciarSesion();

    TestBed.inject(HttpClient).get('/api/user').subscribe({ error: () => undefined });
    http.expectOne('/api/user').flush({ message: 'Unauthenticated.' }, { status: 401, statusText: 'Unauthorized' });

    expect(auth.autenticado()).toBe(false);
    expect(localStorage.getItem('logistica.sesion')).toBeNull();
    expect(navegar).toHaveBeenCalledWith(['/login']);
  });

  it('limpia la sesión al cerrar sesión', () => {
    iniciarSesion();

    auth.logout().subscribe();
    http.expectOne('/api/logout').flush(null, { status: 204, statusText: 'No Content' });

    expect(auth.autenticado()).toBe(false);
  });
});
