$(document).ready(function() {
    "use strict";

    if ($("#my-awesome-dropzone").length > 0) {
        // 1. Инициализируем DataTable
        const table = $("#upload-results-datatable").DataTable({
            paging: true,
            autoWidth: false,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                },
                info: "Отображение с _START_ по _END_ из _TOTAL_ файлов.",
                lengthMenu: "Показывать <select class='form-select form-select-sm ms-1 me-1'>" +
                    "<option value='10'>по 10</option>" +
                    "<option value='20'>по 20</option>" +
                    "<option value='50'>по 50</option>" +
                    "<option value='-1'>Все</option>" +
                    "</select>",
                emptyTable: "Результаты загрузки появятся здесь.",
                search: "Поиск:"
            },
            pageLength: 20,
            columns: [
                { orderable: true, name: "filename" }, // Имя файла
                { orderable: true, name: "size" }, // Размер
                { orderable: true, name: "status" }, // Статус
                { orderable: false, name: "actions" }, // Действия
                { orderable: true, name: "status_sort_key", visible: false } // Скрытый столбец для сортировки
            ],
            columnDefs: [
                { targets: 0, width: '30%' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '45%' },
                { targets: 3, width: '50px' }
            ],
            order: [[4, 'asc']], // Сортировка по скрытому столбцу
            drawCallback: function() {
                $(".dataTables_paginate > .pagination").addClass("pagination-sm");
                $("#upload-results-datatable_length label").addClass("form-label");
            }
        });

        

        // 2. Инициализируем Dropzone
        new Dropzone("#my-awesome-dropzone", {
            paramName: "client_pdfs",
            acceptedFiles: "application/pdf",
            autoProcessQueue: true,
            parallelUploads: 5,
            previewsContainer: false,
            
            init: function() {
                this.on("addedfile", function(file) {
                    const rowNode = table.row.add([
                        file.name,
                        (file.size / 1024).toFixed(1) + " KB",
                        '<div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-1">Обработка...</span>',
                        "—",
                        0 // Приоритет сортировки: 0 (самый высокий) для новых файлов
                    ]).draw(false);

                    file.dtRow = rowNode;
                    $(rowNode.node()).attr('id', `result-${file.upload.uuid}`);
                });

                this.on("success", function(file, response) {
                    const row = file.dtRow;
                    if (!row) return;

                    let statusHtml, actionHtml, sortKey;
                    let res = response;

                    try {
                        if (typeof response === "string") {
                            res = JSON.parse(response);
                        }
                    } catch (e) {
                        res = { status: 'error', message: 'Ошибка ответа сервера' };
                    }

                    // Сбрасываем классы перед установкой новых
                    $(row.node()).removeClass('table-warning table-danger table-success');

                    if (res.status === "success") {
                        statusHtml = `<span class="badge badge-success-lighten">Прикреплена к анкете №${res.client_id}</span>`;
                        actionHtml = "—";
                        sortKey = 3; // Приоритет: 3 (успех)
                    } else if (res.status === "duplicates") {
                        // ЛОГИКА ДЛЯ ДУБЛЕЙ
                        let count = res.candidates ? res.candidates.length : 0;
                        statusHtml = `<span class="badge badge-warning-lighten">Дубли: Выберите анкету</span>
                                      <div class="font-13 mt-1 text-wrap">Найдено совпадений: <b>${count}</b></div>`;

                        $(row.node()).data('candidates', res.candidates);
                        $(row.node()).data('temp_file', res.temp_file);
                        $(row.node()).data('pdf_data', res.pdf_data);
                        
                        actionHtml = `<button type="button" class="btn btn-xs btn-secondary" onclick="modalResolveDuplicates(this)">Выбрать</button>`;
                        sortKey = 1; // Приоритет: 1 (дубликаты)
                    } else {
                        if (res.temp_file) {
                             $(row.node()).data('temp_file', res.temp_file);
                             actionHtml = `<button type="button" class="btn btn-xs btn-secondary" onclick="modalAttachPdfForm(this)">Прикрепить вручную</button>`;
                        } else {
                             actionHtml = "—";
                        }
                        statusHtml = `<span class="badge badge-danger-lighten">Ошибка:</span><div class="font-13 mt-1 text-wrap">${res.message}</div>`;
                        sortKey = 2; // Приоритет: 2 (прочие ошибки)
                    }

                    let rowData = row.data();
                    rowData[2] = statusHtml;
                    rowData[3] = actionHtml;
                    rowData[4] = sortKey; // Обновляем значение в скрытом столбце

                    row.data(rowData).invalidate();
                    table.draw(false); // Перерисовываем таблицу для применения сортировки
                });

                this.on("error", function(file, errorMessage) {
                    const row = file.dtRow;
                    if (!row) return;

                    let errorText = errorMessage;
                    if (typeof errorMessage === "object" && errorMessage.error) {
                        errorText = errorMessage.error;
                    }

                    let rowData = row.data();
                    rowData[2] = `<span class="badge badge-danger-lighten">Ошибка загрузки:</span><div class="font-13 mt-1 text-wrap">${errorText}</div>`;
                    rowData[3] = '<button type="button" class="btn btn-xs btn-secondary" onclick="modalAttachPdfForm()">Прикрепить вручную</button>';
                    rowData[4] = 2; // Приоритет: 2 (прочие ошибки)

                    row.data(rowData).invalidate();
                    table.draw(false); // Перерисовываем таблицу для применения сортировки
                });
            }
        });
    }
});

// --- МОДАЛКА РАЗРЕШЕНИЯ ДУБЛЕЙ ---

let duplicates_table = null;

/**
 * Открывает модальное окно с таблицей анкет-дублей.
 * button — кнопка "Выбрать" из строки результата загрузки.
 */
function modalResolveDuplicates(button) {
    const $row = $(button).closest('tr');

    // --- ДОБАВЛЕНО: Сохраняем ID строки таблицы, чтобы потом обновить её ---
    const rowId = $row.attr('id');
    $('#modal-resolve-duplicates').data('target-row-id', rowId);

    const candidates = $row.data('candidates') || [];
    const temp_file = $row.data('temp_file') || null;
    const pdf_data = $row.data('pdf_data') || null;

    // Сохраняем служебные данные
    $('#duplicates-temp-file').val(temp_file || '');
    $('#duplicates-pdf-data').val(pdf_data ? JSON.stringify(pdf_data) : '');

    // Инициализируем DataTable внутри модалки один раз
    if (!duplicates_table) {
        duplicates_table = $('#duplicates-clients-datatable').DataTable({
            dom: 't',           // только таблица
            paging: false,
            info: false,
            searching: false,
            lengthChange: false,
            ordering: true,    // Включаем сортировку
            columns: [
                { orderable: true }, // ID
                { orderable: true }, // ФИО
                { orderable: true }, // Телефон
                { orderable: true }, // Паспорт
                { orderable: true }, // Города
                { orderable: true }, // Категории
                { orderable: true }, // Семья
                { orderable: true }, // Менеджер
                { orderable: true }, // Агент
                { orderable: true }, // Стоимость
                { orderable: false }  // Действия
            ],
            // --- ИСПРАВЛЕНО: Фиксированная ширина для последней колонки ---
            columnDefs: [
                { targets: -1, width: '80px', className: 'text-center' }
            ]
        });
    }

    // Очищаем старые данные перед заполнением новыми
    duplicates_table.clear();

    // Заполняем таблицу кандидатами
    candidates.forEach(function (c) {
        const full_name = [
            c.last_name || '',
            c.first_name || '',
            c.middle_name || ''
        ].join(' ').trim();

        const phone = c.phone_code
            ? ('+' + c.phone_code + ' ' + (c.phone_number || ''))
            : '—';

        const passport = c.passport_number || '—';
        const cities = c.client_cities_list || '—';

        // Категории (если в выборке появится client_categories_list — отобразим, иначе "—")
        const categories = c.client_categories_list || '—';

        // Семья: если есть family_id — покажем номер семьи, иначе "—"
        const family = c.family_id ? ('№' + c.family_id) : '—';

        // Менеджер и агент
        const manager_name = [
            c.manager_firstname || '',
            c.manager_lastname || ''
        ].join(' ').trim();

        const agent_name = [
            c.agent_firstname || '',
            c.agent_lastname || ''
        ].join(' ').trim();

        const manager = manager_name || '—';
        const agent = agent_name || '—';

        // Стоимость (если есть sale_price — покажем, иначе "—")
        let price = '—';
        if (c.sale_price !== undefined && c.sale_price !== null && c.sale_price !== '') {
            const formattedPrice = parseFloat(c.sale_price).toFixed(2);
            price = `<span class="text-success fw-semibold"><i class="mdi mdi-currency-usd"></i> ${formattedPrice}</span>`;
        }

        const actionsHtml =
            `<a href="#" class="font-18 text-success" onclick="selectDuplicateClient(${c.client_id}, this)" title="Выбрать эту анкету"><i class="mdi mdi-check-circle"></i></a>`;

        duplicates_table.row.add([
            c.client_id,
            full_name || '—',
            phone,
            passport,
            cities,
            categories,
            family,
            manager,
            agent,
            price,
            actionsHtml
        ]);
    });

    duplicates_table.draw();

    // Показываем модальное окно
    $('#modal-resolve-duplicates').modal('show');
}

/**
 * Выбор анкеты в модальном окне и отправка запроса на сервер.
 */
function selectDuplicateClient(client_id, btn) {
    const temp_file = $('#duplicates-temp-file').val();
    // Получаем сохраненные данные из PDF (категория, дата)
    const pdf_data_str = $('#duplicates-pdf-data').val();
    
    if (!client_id || !temp_file) {
        alert('Ошибка данных. Попробуйте обновить страницу.');
        return;
    }

    // Блокируем кнопку, показываем лоадер
    const originalContent = $(btn).html();
    $(btn).addClass('disabled').html('<div class="spinner-border spinner-border-sm text-success" role="status"></div>');

    $.ajax({
        url: '/?form=upload-client-pdfs',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'resolve_duplicate',
            client_id: client_id,
            temp_file: temp_file,
            pdf_data: pdf_data_str // Отправляем данные парсинга
        },
        success: function(res) {
            if (res.status === 'success') {
                // 1. Закрываем модалку
                $('#modal-resolve-duplicates').modal('hide');

                // 2. Находим строку в основной таблице
                const rowId = $('#modal-resolve-duplicates').data('target-row-id');
                const table = $("#upload-results-datatable").DataTable();
                
                if (rowId) {
                    const row = table.row('#' + rowId);

                    if (row.length) {
                        // 3. Обновляем данные строки на "Успех"
                        const rowData = row.data();
                        
                        // Статус (3-я колонка, индекс 2)
                        rowData[2] = `<span class="badge badge-success-lighten">Прикреплена к анкете №${res.client_id}</span>`;
                        // Действия (4-я колонка, индекс 3)
                        rowData[3] = `—`;

                        // Обновляем данные и перерисовываем строку
                        row.data(rowData).invalidate();
                        
                        // Убираем желтый класс со строки
                        $(row.node()).removeClass('table-warning');
                    }
                }

            } else {
                alert('Ошибка: ' + res.message);
                $(btn).removeClass('disabled').html(originalContent);
            }
        },
        error: function() {
            alert('Ошибка сети.');
            $(btn).removeClass('disabled').html(originalContent);
        }
    });
}
