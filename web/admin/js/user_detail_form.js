// Функции для работы с детальной формой пользователя

// Глобальные переменные для умной валидации
var originalFormData = {};
var changedFields = {};

// Инициализация отслеживания изменений
function initChangeTracking(data) {
    // Сохраняем исходные данные
    originalFormData = {
        user_email: data.user?.email || '',
        user_phone: data.user?.phone || '',
        user_is_admin: data.user?.is_admin || '0',
        name: data.company?.name || '',
        tax_id: data.company?.tax_id || '',
        legal_name: data.company?.legal_name || '',
        start_date: data.company?.start_date || '',
        website: data.company?.website || '',
        organization_type: data.company?.organization_type || '',
        main_activity: data.company?.main_activity || '',
        main_product: {
            name: data.products?.main?.name || '',
            tariff_code: data.products?.main?.tariff_code || '',
            description: data.products?.main?.description || '',
            volume_unit: data.products?.main?.volume_unit || '',
            volume_amount: data.products?.main?.volume_amount || '',
            annual_export: data.products?.main?.annual_export || '',
            certifications: data.products?.main?.certifications || ''
        },
        secondary_products: (data.products?.secondary || []).map(p => ({
            id: p.id || null,
            name: p.name || '',
            tariff_code: p.tariff_code || '',
            description: p.description || '',
            volume_unit: p.volume_unit || '',
            volume_amount: p.volume_amount || '',
            annual_export: p.annual_export || ''
        }))
    };
    
    // Если нет данных компании, не инициализируем отслеживание для полей компании
    if (!data.has_company_data) {
        // Инициализируем только для базовых полей пользователя
        setTimeout(() => {
            setupChangeTracking();
        }, 150);
        return;
    }
    
    // Сбрасываем отслеживание изменений
    changedFields = {};
    
        // Убеждаемся, что dropdown'ы правильно заполнены значениями из БД
        setTimeout(() => {
            const isAdminField = document.getElementById('form_user_is_admin');
            if (isAdminField && originalFormData.user_is_admin !== undefined) {
                isAdminField.value = String(originalFormData.user_is_admin);
            }
            
            const orgTypeField = document.getElementById('form_organization_type');
            if (orgTypeField && originalFormData.organization_type) {
                orgTypeField.value = originalFormData.organization_type;
            }
            
            const mainActivityField = document.getElementById('form_main_activity');
            if (mainActivityField && originalFormData.main_activity) {
                mainActivityField.value = originalFormData.main_activity;
            }
            
            const volumeUnitField = document.getElementById('form_main_product_volume_unit');
            if (volumeUnitField && originalFormData.main_product?.volume_unit) {
                volumeUnitField.value = originalFormData.main_product.volume_unit;
            }
            
            // Добавляем обработчики событий для всех редактируемых полей
            setupChangeTracking();
        }, 150);
}

// Настройка отслеживания изменений полей
function setupChangeTracking() {
    // Основные текстовые поля
    const textFields = [
        'form_user_email', 'form_user_phone',
        'form_name', 'form_tax_id', 'form_legal_name', 'form_start_date', 'form_website',
        'form_main_product_name', 'form_main_product_tariff_code', 'form_main_product_description',
        'form_main_product_volume_amount', 'form_main_product_annual_export', 'form_certifications'
    ];
    
    textFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Сохраняем исходное значение для сравнения (из originalFormData)
            const originalValue = getOriginalValue(fieldId);
            
            // Добавляем обработчик (без клонирования, чтобы не потерять значение)
            field.addEventListener('input', function() {
                if (this.value.trim() !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    // Если вернули исходное значение, убираем из измененных
                    delete changedFields[fieldId];
                }
            });
            field.addEventListener('change', function() {
                if (this.value.trim() !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    delete changedFields[fieldId];
                }
            });
        }
    });
    
    // Dropdown поля (select)
    const selectFields = [
        'form_user_is_admin',
        'form_organization_type', 'form_main_activity', 'form_main_product_volume_unit'
    ];
    
    selectFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Сохраняем исходное значение для сравнения (из originalFormData)
            const originalValue = getOriginalValue(fieldId);
            
            // Добавляем обработчик
            field.addEventListener('change', function() {
                if (this.value !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    delete changedFields[fieldId];
                }
            });
        }
    });
    
    // Обработка вторичных продуктов
    document.querySelectorAll('.secondary-product-item input, .secondary-product-item select').forEach(field => {
        field.addEventListener('input', () => {
            const item = field.closest('.secondary-product-item');
            if (item) {
                const index = item.dataset.index;
                markFieldChanged('secondary_product_' + index);
            }
        });
        field.addEventListener('change', () => {
            const item = field.closest('.secondary-product-item');
            if (item) {
                const index = item.dataset.index;
                markFieldChanged('secondary_product_' + index);
            }
        });
    });
}

// Помечает поле как измененное
function markFieldChanged(fieldId) {
    changedFields[fieldId] = true;
}

// Проверяет, было ли поле изменено
function isFieldChanged(fieldId) {
    return changedFields[fieldId] === true;
}

// Получает исходное значение поля из originalFormData
function getOriginalValue(fieldId) {
        const fieldMap = {
        'form_user_email': 'user_email',
        'form_user_phone': 'user_phone',
        'form_user_is_admin': 'user_is_admin',
        'form_name': 'name',
        'form_tax_id': 'tax_id',
        'form_legal_name': 'legal_name',
        'form_start_date': 'start_date',
        'form_website': 'website',
        'form_organization_type': 'organization_type',
        'form_main_activity': 'main_activity',
        'form_main_product_name': 'main_product.name',
        'form_main_product_tariff_code': 'main_product.tariff_code',
        'form_main_product_description': 'main_product.description',
        'form_main_product_volume_unit': 'main_product.volume_unit',
        'form_main_product_volume_amount': 'main_product.volume_amount',
        'form_main_product_annual_export': 'main_product.annual_export',
        'form_certifications': 'main_product.certifications'
    };
    
    const path = fieldMap[fieldId];
    if (!path) return '';
    
    const parts = path.split('.');
    let value = originalFormData;
    for (const part of parts) {
        if (value && typeof value === 'object' && part in value) {
            value = value[part];
        } else {
            return '';
        }
    }
    
    return value || '';
}

// Получает значение поля (текущее или исходное)
function getFieldValue(fieldId, originalValue) {
    if (isFieldChanged(fieldId)) {
        const field = document.getElementById(fieldId);
        if (field) {
            if (field.tagName === 'SELECT') {
                return field.value || '';
            }
            return field.value.trim();
        }
        return '';
    }
    return originalValue || '';
}

function generateUserFormHTML(data, userId) {
    const user = data.user || {};
    const hasCompanyData = data.has_company_data === true; // Явно проверяем, что это true
    const company = data.company || {};
    const addresses = data.addresses || {};
    const contacts = data.contacts || {};
    const socialNetworks = data.social_networks || [];
    const products = data.products || {};
    const exportHistory = data.export_history || {};
    const companyData = data.company_data || {};
    const files = data.files || {};
    
    let html = '<form id="user_detail_form_content" onsubmit="return false;">';
    html += '<input type="hidden" id="form_user_id" value="' + userId + '">';
    
    // Секция 0: Datos Básicos (всегда показываем)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">0. Datos Básicos</h4>';
    
    html += '<div class="form-group"><label>Correo electrónico <span class="req">*</span></label>';
    html += '<input type="email" class="form-control" id="form_user_email" value="' + escapeHtml(user.email || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Teléfono <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_user_phone" value="' + escapeHtml(user.phone || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Es Administrador <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_user_is_admin" required>';
    html += '<option value="0"' + (user.is_admin == 0 ? ' selected' : '') + '>No</option>';
    html += '<option value="1"' + (user.is_admin == 1 ? ' selected' : '') + '>Sí</option>';
    html += '</select></div>';
    
    html += '<div class="form-group"><label>Creado el</label>';
    html += '<div class="readonly-field">' + escapeHtml(user.created_at || '') + '</div></div>';
    
    html += '<div class="form-group"><label>Actualizado el</label>';
    html += '<div class="readonly-field">' + escapeHtml(user.updated_at || '') + '</div></div>';
    
    html += '</div>';
    
    // Если нет данных компании, показываем сообщение и не показываем остальные секции
    if (!hasCompanyData) {
        html += '<div class="user-form-section">';
        html += '<div class="alert alert-info">Este usuario aún no ha completado el registro completo.</div>';
        html += '</div>';
        
        // Кнопка Guardar (только для Datos Básicos)
        html += '<div class="form-actions">';
        html += '<button type="button" class="btn btn-primary" onclick="saveUserBasicData(' + userId + ')">Guardar</button>';
        html += '<div id="save_message" style="margin-top: 10px;"></div>';
        html += '</div>';
        
        html += '</form>';
        
        return html;
    }
    
    // Секция 1: Datos de la Empresa (только если есть данные компании)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">1. Datos de la Empresa</h4>';
    
    html += '<div class="form-group"><label>Nombre de la Empresa <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_name" value="' + escapeHtml(company.name || '') + '" required></div>';
    
    html += '<div class="form-group"><label>CUIT / Identificación Fiscal <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_tax_id" value="' + escapeHtml(company.tax_id || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Razón social <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_legal_name" value="' + escapeHtml(company.legal_name || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Fecha de Inicio de Actividad <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_start_date" value="' + escapeHtml(company.start_date || '') + '" placeholder="dd/mm/yyyy" required></div>';
    
    html += '<div class="form-group"><label>Página web</label>';
    html += '<input type="text" class="form-control" id="form_website" value="' + escapeHtml(company.website || '') + '"></div>';
    
    // Redes sociales (только чтение)
    if (socialNetworks.length > 0) {
        html += '<div class="form-group"><label>Redes sociales</label>';
        html += '<div class="readonly-field">' + socialNetworks.map(sn => escapeHtml(sn.network_type || '') + ': ' + escapeHtml(sn.url || '')).join(', ') + '</div></div>';
    }
    
    // Domicilio Legal (только чтение)
    const legalAddr = addresses.legal || {};
    if (legalAddr.street) {
        html += '<div class="form-group"><label>Domicilio Legal</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(legalAddr.street || '') + ' ' + escapeHtml(legalAddr.street_number || '') + ', ';
        html += escapeHtml(legalAddr.locality || '') + ', ' + escapeHtml(legalAddr.department || '');
        html += '</div></div>';
    }
    
    // Dirección administrativa (только чтение)
    const adminAddr = addresses.admin || {};
    if (adminAddr.street) {
        html += '<div class="form-group"><label>Dirección administrativa</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(adminAddr.street || '') + ' ' + escapeHtml(adminAddr.street_number || '') + ', ';
        html += escapeHtml(adminAddr.locality || '') + ', ' + escapeHtml(adminAddr.department || '');
        html += '</div></div>';
    }
    
    // Persona de Contacto (только чтение)
    if (contacts.contact_person) {
        html += '<div class="form-group"><label>Persona de Contacto</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(contacts.contact_person || '') + ' (' + escapeHtml(contacts.position || '') + '), ';
        html += escapeHtml(contacts.email || '') + ', ' + escapeHtml(contacts.area_code || '') + ' ' + escapeHtml(contacts.phone || '');
        html += '</div></div>';
    }
    
    html += '</div>';
    
    // Секция 2: Clasificación
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">2. Clasificación de la Empresa</h4>';
    
    html += '<div class="form-group"><label>Tipo de Organización <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_organization_type" required>';
    html += '<option value="">...</option>';
    const orgTypes = ['Empresa grande', 'PyME', 'Cooperativa', 'Emprendimiento', 'Startup', 'Clúster', 'Consorcio', 'Otros (especificar)'];
    orgTypes.forEach(type => {
        html += '<option value="' + escapeHtml(type) + '"' + (company.organization_type === type ? ' selected' : '') + '>' + escapeHtml(type) + '</option>';
    });
    html += '</select></div>';
    
    html += '<div class="form-group"><label>Actividad Principal <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_main_activity" required>';
    html += '<option value="">...</option>';
    const activities = ['Agroindustria', 'Industria manufacturera', 'Servicios basados en conocimiento', 'Turismo', 'Economía cultural/creativa', 'Otros (especificar)'];
    activities.forEach(act => {
        html += '<option value="' + escapeHtml(act) + '"' + (company.main_activity === act ? ' selected' : '') + '>' + escapeHtml(act) + '</option>';
    });
    html += '</select></div>';
    
    html += '</div>';
    
    // Секция 3: Productos
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">3. Información sobre Productos y Servicios</h4>';
    
    const mainProduct = products.main || {};
    html += '<div class="form-group"><label>Producto o servicio principal <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_main_product_name" value="' + escapeHtml(mainProduct.name || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Código Arancelario <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_main_product_tariff_code" value="' + escapeHtml(mainProduct.tariff_code || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Descripción <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_main_product_description" value="' + escapeHtml(mainProduct.description || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Unidad de Volumen <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_main_product_volume_unit" required>';
    html += '<option value="">...</option>';
    const units = ['kg', 'toneladas', 'litros', 'unidades', 'horas'];
    units.forEach(unit => {
        html += '<option value="' + escapeHtml(unit) + '"' + (mainProduct.volume_unit === unit ? ' selected' : '') + '>' + escapeHtml(unit) + '</option>';
    });
    html += '</select></div>';
    
    html += '<div class="form-group"><label>Cantidad de Volumen <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_main_product_volume_amount" value="' + escapeHtml(mainProduct.volume_amount || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Exportación Anual (USD) <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_main_product_annual_export" value="' + escapeHtml(mainProduct.annual_export || '') + '" required></div>';
    
    // Фото основного продукта (только просмотр)
    if (files.product_photo && files.product_photo.length > 0) {
        html += '<div class="form-group"><label>Foto del Producto</label>';
        html += displayFiles(files.product_photo);
        html += '</div>';
    }
    
    // Вторичные продукты
    const secondaryProducts = products.secondary || [];
    if (secondaryProducts.length > 0) {
        html += '<div class="form-group"><label>Productos Secundarios</label>';
        html += '<div id="secondary_products_list">';
        secondaryProducts.forEach((product, index) => {
            html += generateSecondaryProductHTML(product, index);
        });
        html += '</div></div>';
    }
    
    // Certificaciones
    html += '<div class="form-group"><label>Certificaciones <span class="req">*</span></label>';
    html += '<textarea class="form-control" id="form_certifications" required>' + escapeHtml(mainProduct.certifications || '') + '</textarea></div>';
    
    // Exportación Anual (только чтение)
    if (exportHistory['2022'] || exportHistory['2023'] || exportHistory['2024']) {
        html += '<div class="form-group"><label>Exportación Anual (USD)</label>';
        html += '<div class="readonly-field">';
        html += '2022: ' + (exportHistory['2022'] || 'N/A') + ', ';
        html += '2023: ' + (exportHistory['2023'] || 'N/A') + ', ';
        html += '2024: ' + (exportHistory['2024'] || 'N/A');
        html += '</div></div>';
    }
    
    // Mercados (только чтение)
    if (companyData.current_markets && Array.isArray(companyData.current_markets)) {
        html += '<div class="form-group"><label>Mercados Actuales</label>';
        html += '<div class="readonly-field">' + companyData.current_markets.join(', ') + '</div></div>';
    }
    
    if (companyData.target_markets) {
        html += '<div class="form-group"><label>Mercados de Interés</label>';
        html += '<div class="readonly-field">' + escapeHtml(companyData.target_markets) + '</div></div>';
    }
    
    html += '</div>';
    
    // Секция 4: Competitividad (только чтение)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">4. Competitividad y Diferenciación</h4>';
    
    if (companyData.differentiation_factors && Array.isArray(companyData.differentiation_factors)) {
        html += '<div class="form-group"><label>Factores de Diferenciación</label>';
        html += '<div class="readonly-field">' + companyData.differentiation_factors.join(', ') + '</div></div>';
    }
    
    if (companyData.competitiveness) {
        const comp = companyData.competitiveness;
        if (comp.history) {
            html += '<div class="form-group"><label>Historia de la Empresa</label>';
            html += '<div class="readonly-field">' + escapeHtml(comp.history) + '</div></div>';
        }
        if (comp.awards) {
            html += '<div class="form-group"><label>Premios</label>';
            html += '<div class="readonly-field">' + escapeHtml(comp.awards) + '</div></div>';
        }
        if (comp.fairs !== undefined) {
            html += '<div class="form-group"><label>Ferias</label>';
            html += '<div class="readonly-field">' + (comp.fairs ? 'Sí' : 'No') + '</div></div>';
        }
        if (comp.rounds !== undefined) {
            html += '<div class="form-group"><label>Rondas</label>';
            html += '<div class="readonly-field">' + (comp.rounds ? 'Sí' : 'No') + '</div></div>';
        }
        if (comp.export_experience) {
            html += '<div class="form-group"><label>Experiencia Exportadora</label>';
            html += '<div class="readonly-field">' + escapeHtml(comp.export_experience) + '</div></div>';
        }
        if (comp.commercial_references) {
            html += '<div class="form-group"><label>Referencias comerciales</label>';
            html += '<div class="readonly-field">' + escapeHtml(comp.commercial_references) + '</div></div>';
        }
    }
    
    html += '</div>';
    
    // Секция 5: Visual (только просмотр)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">5. Información Visual y Promocional</h4>';
    
    if (files.logo && files.logo.length > 0) {
        html += '<div class="form-group"><label>Logo de la Empresa</label>';
        html += displayFiles(files.logo);
        html += '</div>';
    }
    
    if (files.process_photo && files.process_photo.length > 0) {
        html += '<div class="form-group"><label>Fotos de los Procesos/Servicios</label>';
        html += displayFiles(files.process_photo);
        html += '</div>';
    }
    
    if (files.digital_catalog && files.digital_catalog.length > 0) {
        html += '<div class="form-group"><label>Catálogo Digital</label>';
        html += displayFiles(files.digital_catalog);
        html += '</div>';
    }
    
    if (files.institutional_video && files.institutional_video.length > 0) {
        html += '<div class="form-group"><label>Video Institucional</label>';
        html += displayFiles(files.institutional_video);
        html += '</div>';
    }
    
    html += '</div>';
    
    // Секция 6: Logística (только чтение)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">6. Logística y Distribución</h4>';
    
    if (companyData.logistics) {
        const log = companyData.logistics;
        if (log.export_capacity !== undefined) {
            html += '<div class="form-group"><label>Capacidad de Exportación</label>';
            html += '<div class="readonly-field">' + (log.export_capacity ? 'Sí' : 'No');
            if (log.export_capacity && log.estimated_term) {
                html += ' (Plazo: ' + escapeHtml(log.estimated_term) + ' meses)';
            }
            html += '</div></div>';
        }
        if (log.infrastructure) {
            html += '<div class="form-group"><label>Infraestructura Logística</label>';
            html += '<div class="readonly-field">' + escapeHtml(log.infrastructure) + '</div></div>';
        }
        if (log.ports_airports) {
            html += '<div class="form-group"><label>Puertos/Aeropuertos</label>';
            html += '<div class="readonly-field">' + escapeHtml(log.ports_airports) + '</div></div>';
        }
    }
    
    html += '</div>';
    
    // Секция 7: Necesidades (только чтение)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">7. Necesidades y Expectativas</h4>';
    
    if (companyData.needs && Array.isArray(companyData.needs)) {
        html += '<div class="form-group"><label>Necesidades</label>';
        html += '<div class="readonly-field">' + companyData.needs.join(', ') + '</div></div>';
    }
    
    if (companyData.expectations) {
        const exp = companyData.expectations;
        if (exp.interest_participate !== undefined) {
            html += '<div class="form-group"><label>Interés en Participar</label>';
            html += '<div class="readonly-field">' + (exp.interest_participate ? 'Sí' : 'No') + '</div></div>';
        }
        if (exp.training_availability !== undefined) {
            html += '<div class="form-group"><label>Disponibilidad para Capacitaciones</label>';
            html += '<div class="readonly-field">' + (exp.training_availability ? 'Sí' : 'No') + '</div></div>';
        }
    }
    
    html += '</div>';
    
    // Секция 8: Validación (только чтение)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">8. Validación y Consentimiento</h4>';
    
    if (companyData.consents) {
        const cons = companyData.consents;
        html += '<div class="form-group"><label>Autorizaciones</label>';
        html += '<div class="readonly-field">';
        html += 'Autorización para Difundir: ' + (cons.authorization_publish === 'si' ? 'Sí' : 'No') + '<br>';
        html += 'Autorización de Publicación: ' + (cons.authorization_publication === 'si' ? 'Sí' : 'No') + '<br>';
        html += 'Acepto ser Contactado: ' + (cons.accept_contact === 'si' ? 'Sí' : 'No');
        html += '</div></div>';
    }
    
    html += '</div>';
    
    // Кнопка Guardar
    html += '<div class="form-actions">';
    html += '<button type="button" class="btn btn-primary" onclick="saveUserFullData(' + userId + ')">Guardar</button>';
    html += '<div id="save_message" style="margin-top: 10px;"></div>';
    html += '</div>';
    
    html += '</form>';
    
    return html;
}

// Функция для сохранения только базовых данных пользователя (когда нет данных компании)
function saveUserBasicData(userId) {
    const errors = [];
    
    // Валидация базовых полей
    const emailValue = document.getElementById('form_user_email')?.value.trim() || '';
    if (!emailValue) {
        errors.push('Correo electrónico');
    } else {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailValue)) {
            errors.push('Correo electrónico (formato inválido)');
        }
    }
    
    const phoneValue = document.getElementById('form_user_phone')?.value.trim() || '';
    if (!phoneValue) {
        errors.push('Teléfono');
    }
    
    const isAdminValue = document.getElementById('form_user_is_admin')?.value || '0';
    
    if (errors.length > 0) {
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Por favor, complete los campos obligatorios: ' + errors.join(', ') + '</div>';
        return;
    }
    
    const formData = {
        user_id: userId,
        user_email: emailValue,
        user_phone: phoneValue,
        user_is_admin: isAdminValue
    };
    
    document.getElementById('save_message').innerHTML = '<div class="alert alert-info">Guardando...</div>';
    
    const basePathValue = window.basePath || basePath || '';
    fetch(basePathValue + 'includes/users_update_basic_data_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-success">Datos guardados correctamente</div>';
            // Перезагружаем данные после успешного сохранения
            setTimeout(() => {
                loadUserFullData(userId);
            }, 1000);
        } else {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    });
}

function generateSecondaryProductHTML(product, index) {
    let html = '<div class="secondary-product-item" data-index="' + index + '">';
    html += '<input type="hidden" class="sec-product-id" value="' + (product.id || '') + '">';
    html += '<div class="form-group"><label>Nombre</label>';
    html += '<input type="text" class="form-control sec-product-name" value="' + escapeHtml(product.name || '') + '"></div>';
    html += '<div class="form-group"><label>Código Arancelario</label>';
    html += '<input type="text" class="form-control sec-product-tariff" value="' + escapeHtml(product.tariff_code || '') + '"></div>';
    html += '<div class="form-group"><label>Descripción</label>';
    html += '<input type="text" class="form-control sec-product-desc" value="' + escapeHtml(product.description || '') + '"></div>';
    html += '<div class="form-group"><label>Unidad</label>';
    html += '<select class="form-control sec-product-unit">';
    html += '<option value="">...</option>';
    const units = ['kg', 'toneladas', 'litros', 'unidades', 'horas'];
    units.forEach(unit => {
        html += '<option value="' + escapeHtml(unit) + '"' + (product.volume_unit === unit ? ' selected' : '') + '>' + escapeHtml(unit) + '</option>';
    });
    html += '</select></div>';
    html += '<div class="form-group"><label>Cantidad</label>';
    html += '<input type="text" class="form-control sec-product-amount" value="' + escapeHtml(product.volume_amount || '') + '"></div>';
    html += '<div class="form-group"><label>Exportación Anual (USD)</label>';
    html += '<input type="text" class="form-control sec-product-export" value="' + escapeHtml(product.annual_export || '') + '"></div>';
    html += '</div>';
    return html;
}

function displayFiles(files) {
    if (!files || files.length === 0) return '';
    
    let html = '<div class="files-preview">';
    files.forEach(file => {
        const isImage = file.mime_type && file.mime_type.startsWith('image/');
        const isVideo = file.mime_type && file.mime_type.startsWith('video/');
        const isPDF = file.mime_type === 'application/pdf';
        
        html += '<div class="file-item-preview">';
        
        if (isImage) {
            html += '<img src="' + escapeHtml(file.url) + '" alt="' + escapeHtml(file.name) + '" style="max-width: 200px; max-height: 150px; margin: 5px;">';
        } else if (isVideo) {
            html += '<video src="' + escapeHtml(file.url) + '" controls style="max-width: 300px; max-height: 200px; margin: 5px;"></video>';
        } else {
            html += '<span>📄 ' + escapeHtml(file.name) + '</span>';
        }
        
        html += '<br><a href="' + escapeHtml(file.url) + '" target="_blank" download>Descargar: ' + escapeHtml(file.name) + '</a>';
        html += '</div>';
    });
    html += '</div>';
    return html;
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Сохранение данных
function saveUserFullData(userId) {
    const errors = validateForm();
    
    if (errors.length > 0) {
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Por favor, complete los campos obligatorios:<ul>' + 
            errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>';
        return;
    }
    
    // Сбор данных с использованием умной логики: измененные поля + исходные значения для неизмененных
    const formData = {
        user_id: userId,
        user_email: getFieldValue('form_user_email', originalFormData.user_email),
        user_phone: getFieldValue('form_user_phone', originalFormData.user_phone),
        user_is_admin: isFieldChanged('form_user_is_admin') 
            ? (document.getElementById('form_user_is_admin')?.value || '0') 
            : (originalFormData.user_is_admin || '0'),
        name: getFieldValue('form_name', originalFormData.name),
        tax_id: getFieldValue('form_tax_id', originalFormData.tax_id),
        legal_name: getFieldValue('form_legal_name', originalFormData.legal_name),
        start_date: getFieldValue('form_start_date', originalFormData.start_date),
        website: getFieldValue('form_website', originalFormData.website),
        organization_type: isFieldChanged('form_organization_type') 
            ? (document.getElementById('form_organization_type')?.value || '') 
            : (originalFormData.organization_type || ''),
        main_activity: isFieldChanged('form_main_activity') 
            ? (document.getElementById('form_main_activity')?.value || '') 
            : (originalFormData.main_activity || ''),
        main_product: {
            name: getFieldValue('form_main_product_name', originalFormData.main_product?.name || ''),
            tariff_code: getFieldValue('form_main_product_tariff_code', originalFormData.main_product?.tariff_code || ''),
            description: getFieldValue('form_main_product_description', originalFormData.main_product?.description || ''),
            volume_unit: isFieldChanged('form_main_product_volume_unit')
                ? (document.getElementById('form_main_product_volume_unit')?.value || '')
                : (originalFormData.main_product?.volume_unit || ''),
            volume_amount: getFieldValue('form_main_product_volume_amount', originalFormData.main_product?.volume_amount || ''),
            annual_export: getFieldValue('form_main_product_annual_export', originalFormData.main_product?.annual_export || ''),
            certifications: getFieldValue('form_certifications', originalFormData.main_product?.certifications || '')
        },
        secondary_products: []
    };
    
    // Сбор вторичных продуктов
    document.querySelectorAll('.secondary-product-item').forEach(item => {
        const secProduct = {
            id: item.querySelector('.sec-product-id').value || null,
            name: item.querySelector('.sec-product-name').value.trim(),
            tariff_code: item.querySelector('.sec-product-tariff').value.trim(),
            description: item.querySelector('.sec-product-desc').value.trim(),
            volume_unit: item.querySelector('.sec-product-unit').value,
            volume_amount: item.querySelector('.sec-product-amount').value.trim(),
            annual_export: item.querySelector('.sec-product-export').value.trim()
        };
        
        if (secProduct.name || secProduct.id) {
            formData.secondary_products.push(secProduct);
        }
    });
    
    // Отправка
    document.getElementById('save_message').innerHTML = '<div class="alert alert-info">Guardando...</div>';
    
    const basePathValue = window.basePath || basePath || '';
    fetch(basePathValue + 'includes/users_update_full_data_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-success">' + (data.res || 'Datos guardados correctamente') + '</div>';
            // Сбрасываем отслеживание изменений после успешного сохранения
            changedFields = {};
            setTimeout(() => {
                loadUserFullData(userId); // Перезагружаем данные
            }, 1000);
        } else {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    });
}

function validateForm() {
    const errors = [];
    
    // Умная валидация: проверяем только измененные поля или обязательные поля, которые пустые в исходных данных
    
    // Email
    const emailValue = getFieldValue('form_user_email', originalFormData.user_email);
    if (isFieldChanged('form_user_email')) {
        if (!emailValue) {
            errors.push('Correo electrónico');
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailValue)) {
                errors.push('Correo electrónico (formato inválido)');
            }
        }
    } else if (!originalFormData.user_email) {
        errors.push('Correo electrónico');
    }
    
    // Teléfono
    const phoneValue = getFieldValue('form_user_phone', originalFormData.user_phone);
    if (isFieldChanged('form_user_phone')) {
        if (!phoneValue) errors.push('Teléfono');
    } else if (!originalFormData.user_phone) {
        errors.push('Teléfono');
    }
    
    // Es Administrador
    const isAdminField = document.getElementById('form_user_is_admin');
    const isAdminValue = isFieldChanged('form_user_is_admin') ? (isAdminField ? isAdminField.value : '0') : originalFormData.user_is_admin;
    if (isFieldChanged('form_user_is_admin')) {
        if (isAdminValue === '' || isAdminValue === null) errors.push('Es Administrador');
    } else if (originalFormData.user_is_admin === '' || originalFormData.user_is_admin === null) {
        errors.push('Es Administrador');
    }
    
    // Nombre de la Empresa
    const nameValue = getFieldValue('form_name', originalFormData.name);
    if (isFieldChanged('form_name')) {
        if (!nameValue) errors.push('Nombre de la Empresa');
    } else if (!originalFormData.name) {
        errors.push('Nombre de la Empresa');
    }
    
    // CUIT
    const taxIdValue = getFieldValue('form_tax_id', originalFormData.tax_id);
    if (isFieldChanged('form_tax_id')) {
        if (!taxIdValue) errors.push('CUIT');
    } else if (!originalFormData.tax_id) {
        errors.push('CUIT');
    }
    
    // Razón social
    const legalNameValue = getFieldValue('form_legal_name', originalFormData.legal_name);
    if (isFieldChanged('form_legal_name')) {
        if (!legalNameValue) errors.push('Razón social');
    } else if (!originalFormData.legal_name) {
        errors.push('Razón social');
    }
    
    // Fecha de Inicio
    const startDateValue = getFieldValue('form_start_date', originalFormData.start_date);
    if (isFieldChanged('form_start_date')) {
        if (!startDateValue) {
            errors.push('Fecha de Inicio');
        } else {
            const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
            if (!datePattern.test(startDateValue)) {
                errors.push('Fecha de Inicio (formato: dd/mm/yyyy)');
            }
        }
    } else if (!originalFormData.start_date) {
        errors.push('Fecha de Inicio');
    } else {
        const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
        if (originalFormData.start_date && !datePattern.test(originalFormData.start_date)) {
            errors.push('Fecha de Inicio (formato: dd/mm/yyyy)');
        }
    }
    
    // Tipo de Organización
    const orgTypeField = document.getElementById('form_organization_type');
    const orgTypeValue = isFieldChanged('form_organization_type') ? (orgTypeField ? orgTypeField.value : '') : originalFormData.organization_type;
    if (isFieldChanged('form_organization_type')) {
        if (!orgTypeValue) errors.push('Tipo de Organización');
    } else if (!originalFormData.organization_type) {
        errors.push('Tipo de Organización');
    }
    
    // Actividad Principal
    const mainActivityField = document.getElementById('form_main_activity');
    const mainActivityValue = isFieldChanged('form_main_activity') ? (mainActivityField ? mainActivityField.value : '') : originalFormData.main_activity;
    if (isFieldChanged('form_main_activity')) {
        if (!mainActivityValue) errors.push('Actividad Principal');
    } else if (!originalFormData.main_activity) {
        errors.push('Actividad Principal');
    }
    
    // Основной продукт
    const mainProduct = originalFormData.main_product || {};
    const mainProductNameValue = getFieldValue('form_main_product_name', mainProduct.name);
    if (isFieldChanged('form_main_product_name')) {
        if (!mainProductNameValue) errors.push('Producto principal');
    } else if (!mainProduct.name) {
        errors.push('Producto principal');
    }
    
    const mainProductTariffValue = getFieldValue('form_main_product_tariff_code', mainProduct.tariff_code);
    if (isFieldChanged('form_main_product_tariff_code')) {
        if (!mainProductTariffValue) errors.push('Código Arancelario');
    } else if (!mainProduct.tariff_code) {
        errors.push('Código Arancelario');
    }
    
    const mainProductDescValue = getFieldValue('form_main_product_description', mainProduct.description);
    if (isFieldChanged('form_main_product_description')) {
        if (!mainProductDescValue) errors.push('Descripción del producto');
    } else if (!mainProduct.description) {
        errors.push('Descripción del producto');
    }
    
    const mainProductUnitField = document.getElementById('form_main_product_volume_unit');
    const mainProductUnitValue = isFieldChanged('form_main_product_volume_unit') ? (mainProductUnitField ? mainProductUnitField.value : '') : mainProduct.volume_unit;
    if (isFieldChanged('form_main_product_volume_unit')) {
        if (!mainProductUnitValue) errors.push('Unidad de Volumen');
    } else if (!mainProduct.volume_unit) {
        errors.push('Unidad de Volumen');
    }
    
    const mainProductAmountValue = getFieldValue('form_main_product_volume_amount', mainProduct.volume_amount);
    if (isFieldChanged('form_main_product_volume_amount')) {
        if (!mainProductAmountValue) errors.push('Cantidad de Volumen');
    } else if (!mainProduct.volume_amount) {
        errors.push('Cantidad de Volumen');
    }
    
    const mainProductExportValue = getFieldValue('form_main_product_annual_export', mainProduct.annual_export);
    if (isFieldChanged('form_main_product_annual_export')) {
        if (!mainProductExportValue) errors.push('Exportación Anual (USD)');
    } else if (!mainProduct.annual_export) {
        errors.push('Exportación Anual (USD)');
    }
    
    const certificationsValue = getFieldValue('form_certifications', mainProduct.certifications);
    if (isFieldChanged('form_certifications')) {
        if (!certificationsValue) errors.push('Certificaciones');
    } else if (!mainProduct.certifications) {
        errors.push('Certificaciones');
    }
    
    return errors;
}

