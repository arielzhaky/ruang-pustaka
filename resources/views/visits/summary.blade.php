<div class="row g-3">
@foreach(['day'=>['Pengunjung harian',$visitDate->format('d/m/Y')], 'month'=>['Pengunjung bulanan',$visitDate->translatedFormat('F Y')], 'year'=>['Pengunjung tahunan',$visitDate->format('Y')]] as $range=>[$label,$caption])
<div class="col-md-4"><a class="dashboard-stat stat-tone-{{ $range === 'day' ? 'violet' : ($range === 'month' ? 'blue' : 'mint') }} d-block text-decoration-none h-100" href="{{ route('visits.index', ['date'=>$visitDate->toDateString(), 'period'=>$range]) }}#visit-details"><span>{{ $label }}</span><strong>{{ $visitTotals[$range] }}</strong><small>{{ $caption }} · Lihat detail siswa →</small></a></div>
@endforeach
</div>
