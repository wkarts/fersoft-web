# ADP Leitor + Monitoramento de Pesagem

## Instalação
1. Executar migrations:
```bash
php artisan migrate
```
2. Acessar tela de conferência:
- `/balancas/leitor`

## Fluxo
- Front chama Laravel (`/balancas/leitor/{id}/read`).
- Laravel chama backend ADP via `BalancaLeitorService`.
- Evidências são capturadas em `/balancas/leitor/{id}/evidence`.

## Segurança
- Tokens ficam no backend e devem ser criptografados no cadastro.
- Nunca expor token no JavaScript.

## Testes manuais
- Validar `open/read/close` com balança ativa.
- Validar modo offline sem travamento da interface.
- Validar captura de evidência com snapshot.
