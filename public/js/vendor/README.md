# Bibliotecas independentes do tema

Os arquivos JavaScript nesta pasta foram separados por pacote a partir do bundle de dependências de terceiros já distribuído pela aplicação. Eles conservam as licenças e os créditos upstream em seus cabeçalhos ou no próprio código. Não contêm `KTApp`, `KTUtil`, `KTLayout` nem o runtime de layout do Metronic.

Ordem de carregamento para o layout autenticado:

1. `jquery-3.4.1.js`
2. `popper-1.16.1.js`
3. `bootstrap-4.5.3.js`
4. `moment-2.29.1.js`
5. `bootstrap-datepicker-1.9.0.js`
6. `bootstrap-timepicker-0.5.2.js`
7. `daterangepicker-3.1.js`
8. `select2-4.0.13.js`
9. `inputmask-4.0.9.js`
10. `apexcharts-3.22.0.js`
11. `sweetalert2-9.17.2.js` (necessário para as views que chamam `Swal.fire()`)
12. `/js/fer-runtime.js` (compatibilidade própria com os IDs e chamadas históricos)

Os estilos dos controles de data, Select2 e SweetAlert2 estão em `/css/vendor/`. O Bootstrap 4.5.3 já existe em `/css/bootstrap4_5_1.min.css`; os scripts de negócio e o FullCalendar autônomo continuam nos caminhos próprios da aplicação. Carregue o SweetAlert2 antes de `/js/sweetalert.min.js`, pois essa biblioteca independente mantém a API clássica `swal()` que outras views utilizam.

As APIs `KT*` do adapter preservam somente chamadas presentes nas views existentes. Ao migrar uma view, prefira `FerRuntime` e os controles nativos. Não inclua scripts de demonstração do antigo tema.
