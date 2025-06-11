<h5 class="mb-3">Информация о договоре и финансах</h5>
<div class="row">
    <div class="col-12 col-md-6 mb-3 mb-md-0">
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="40%">Дата договора:</th>
                        <td>{{ $project->contract_date ? $project->contract_date->format('d.m.Y') : 'Не указана' }}</td>
                    </tr>
                    <tr>
                        <th>Номер договора:</th>
                        <td>{{ $project->contract_number ?? 'Не указан' }}</td>
                    </tr>
                    <tr>
                        <th>Дата начала работ:</th>
                        <td>{{ $project->work_start_date ? $project->work_start_date->format('d.m.Y') : 'Не указана' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="40%">Сумма на работы:</th>
                        <td>{{ number_format($project->work_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                    <tr>
                        <th>Сумма на материалы:</th>
                        <td>{{ number_format($project->materials_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                    <tr>
                        <th>Общая сумма:</th>
                        <td class="fw-bold">{{ number_format($project->total_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mb-4">
    <?php if($project->finance_link): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="ratio ratio-16x9">
                    <iframe src="{{ $project->finance_link }}" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Финансовый учет</h5>
            </div>
            <div class="card-body">
                <!-- Основные работы -->
                <div class="finance-section mb-4">
                    <h6 class="finance-section-title">Основные работы</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="mainWorksTable">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th style="width: 150px;">Дата оплаты</th>
                                    <th style="width: 150px;">Всего (₽)</th>
                                    <th style="width: 150px;">Оплачено (₽)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($project->mainWorks as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->payment_date ? $item->payment_date->format('d.m.Y') : '' }}</td>
                                        <td class="text-end">{{ number_format($item->total_amount, 2, ',', ' ') }} ₽</td>
                                        <td class="text-end">{{ number_format($item->paid_amount, 2, ',', ' ') }} ₽</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Основные материалы -->
                <div class="finance-section mb-4">
                    <h6 class="finance-section-title">Основные материалы</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="mainMaterialsTable">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th style="width: 150px;">Дата оплаты</th>
                                    <th style="width: 150px;">Всего (₽)</th>
                                    <th style="width: 150px;">Оплачено (₽)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($project->mainMaterials as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->payment_date ? $item->payment_date->format('d.m.Y') : '' }}</td>
                                        <td class="text-end">{{ number_format($item->total_amount, 2, ',', ' ') }} ₽</td>
                                        <td class="text-end">{{ number_format($item->paid_amount, 2, ',', ' ') }} ₽</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Дополнительные работы -->
                @if($project->additionalWorks->count() > 0)
                <div class="finance-section mb-4">
                    <h6 class="finance-section-title">Дополнительные работы</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="additionalWorksTable">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th style="width: 150px;">Дата оплаты</th>
                                    <th style="width: 150px;">Всего (₽)</th>
                                    <th style="width: 150px;">Оплачено (₽)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->additionalWorks as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->payment_date ? $item->payment_date->format('d.m.Y') : '' }}</td>
                                        <td class="text-end">{{ number_format($item->total_amount, 2, ',', ' ') }} ₽</td>
                                        <td class="text-end">{{ number_format($item->paid_amount, 2, ',', ' ') }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                
                <!-- Дополнительные материалы -->
                @if($project->additionalMaterials->count() > 0)
                <div class="finance-section mb-4">
                    <h6 class="finance-section-title">Дополнительные материалы</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="additionalMaterialsTable">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th style="width: 150px;">Дата оплаты</th>
                                    <th style="width: 150px;">Всего (₽)</th>
                                    <th style="width: 150px;">Оплачено (₽)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->additionalMaterials as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->payment_date ? $item->payment_date->format('d.m.Y') : '' }}</td>
                                        <td class="text-end">{{ number_format($item->total_amount, 2, ',', ' ') }} ₽</td>
                                        <td class="text-end">{{ number_format($item->paid_amount, 2, ',', ' ') }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                
                <!-- Транспортировка -->
                @if($project->transportationItems->count() > 0)
                <div class="finance-section mb-4">
                    <h6 class="finance-section-title">Транспортировка</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="transportationTable">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th style="width: 150px;">Дата оплаты</th>
                                    <th style="width: 150px;">Всего (₽)</th>
                                    <th style="width: 150px;">Оплачено (₽)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->transportationItems as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->payment_date ? $item->payment_date->format('d.m.Y') : '' }}</td>
                                        <td class="text-end">{{ number_format($item->total_amount, 2, ',', ' ') }} ₽</td>
                                        <td class="text-end">{{ number_format($item->paid_amount, 2, ',', ' ') }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                
                <!-- Итоги -->
                <div class="card bg-light">
                    <div class="card-body">
                        @php
                            $totalWorks = $project->mainWorks->sum('total_amount') + $project->additionalWorks->sum('total_amount');
                            $totalMaterials = $project->mainMaterials->sum('total_amount') + $project->additionalMaterials->sum('total_amount') + $project->transportationItems->sum('total_amount');
                            $paidWorks = $project->mainWorks->sum('paid_amount') + $project->additionalWorks->sum('paid_amount');
                            $paidMaterials = $project->mainMaterials->sum('paid_amount') + $project->additionalMaterials->sum('paid_amount') + $project->transportationItems->sum('paid_amount');
                        @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Всего по работам:</h6>
                                    <span>{{ number_format($totalWorks, 2, ',', ' ') }} ₽</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Всего по материалам:</h6>
                                    <span>{{ number_format($totalMaterials, 2, ',', ' ') }} ₽</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Оплачено по работам:</h6>
                                    <span>{{ number_format($paidWorks, 2, ',', ' ') }} ₽</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Оплачено по материалам:</h6>
                                    <span>{{ number_format($paidMaterials, 2, ',', ' ') }} ₽</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0 fw-bold">ИТОГО:</h5>
                            <span class="fw-bold">{{ number_format($totalWorks + $totalMaterials, 2, ',', ' ') }} ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
