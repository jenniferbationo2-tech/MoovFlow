<script setup>
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    PointElement, LineElement, CategoryScale, LinearScale, Filler,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, PointElement, LineElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
    chartData: { type: Object, required: true },
    chartOptions: { type: Object, default: () => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
        },
    }) },
})
</script>

<template>
    <Line :data="chartData" :options="chartOptions"/>
</template>