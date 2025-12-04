import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import AutoNumeric from 'autonumeric';

import cashierHandler from './cashier';
import financeHandler from './finance'; 

Alpine.plugin(persist);

Alpine.data('cashierHandler', cashierHandler);
Alpine.data('financeHandler', financeHandler); 

window.AutoNumeric = AutoNumeric;

window.Alpine = Alpine;
Alpine.start();