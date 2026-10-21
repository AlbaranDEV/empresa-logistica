import { HttpClient } from '@angular/common/http';
import { computed, inject, Service, signal } from '@angular/core';
import { finalize, map, Observable, tap } from 'rxjs';
import { Sesion } from './models';

const CLAVE_SESION = 'logistica.sesion';

@Service()
export class Auth {
  private readonly http = inject(HttpClient);
  private readonly sesion = signal<Sesion | null>(leerSesion());

  readonly usuario = computed(() => this.sesion()?.user ?? null);
  readonly token = computed(() => this.sesion()?.token ?? null);
  readonly autenticado = computed(() => this.sesion() !== null);
  readonly esAdmin = computed(() => this.usuario()?.rol === 'admin');

  login(email: string, password: string): Observable<void> {
    return this.http.post<Sesion>('/api/login', { email, password }).pipe(
      tap((sesion) => this.guardar(sesion)),
      map(() => undefined),
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>('/api/logout', {}).pipe(finalize(() => this.limpiar()));
  }

  limpiar(): void {
    this.guardar(null);
  }

  private guardar(sesion: Sesion | null): void {
    this.sesion.set(sesion);
    try {
      if (sesion) {
        localStorage.setItem(CLAVE_SESION, JSON.stringify(sesion));
      } else {
        localStorage.removeItem(CLAVE_SESION);
      }
    } catch {
      // Almacenamiento no disponible: la sesión vive solo en memoria.
    }
  }
}

function leerSesion(): Sesion | null {
  try {
    const valor = localStorage.getItem(CLAVE_SESION);
    return valor ? (JSON.parse(valor) as Sesion) : null;
  } catch {
    return null;
  }
}
