import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Decimation,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from "chart.js";

let isOverviewChartRegistryInitialized = false;

function ensureOverviewChartRegistry(): void {
    if (isOverviewChartRegistryInitialized) {
        return;
    }

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        BarElement,
        Tooltip,
        Legend,
        Filler,
        Decimation,
    );

    isOverviewChartRegistryInitialized = true;
}

export { ensureOverviewChartRegistry };
