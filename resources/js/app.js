import './bootstrap';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import Chart from 'chart.js/auto';

window.FullCalendar = {
    Calendar,
    dayGridPlugin,
    timeGridPlugin,
    listPlugin,
};
window.Chart = Chart;
