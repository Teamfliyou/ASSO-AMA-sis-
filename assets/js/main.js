// Fichier : assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Vérifie si la variable globale ABSENCE_DATA (injectée par PHP) existe
    if (typeof ABSENCE_DATA !== 'undefined' && ABSENCE_DATA.labels && ABSENCE_DATA.labels.length > 0) {
        
        const ctx = document.getElementById('absencesChart');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ABSENCE_DATA.labels,
                datasets: [
                    {
                        label: 'Absences Non Justifiées',
                        data: ABSENCE_DATA.non_justifiees,
                        backgroundColor: 'rgba(234, 67, 53, 0.7)', // Rouge vif
                        borderColor: 'rgba(234, 67, 53, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Absences Justifiées',
                        data: ABSENCE_DATA.justifiees,
                        backgroundColor: 'rgba(52, 168, 83, 0.7)', // Vert
                        borderColor: 'rgba(52, 168, 83, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Élèves'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de Jours d\'Absence'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Absences Totales par Élève (Non Justifiées / Justifiées)'
                    }
                }
            }
        });
    }
});