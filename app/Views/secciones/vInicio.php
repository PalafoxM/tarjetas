
    <!-- Page Content-->
    <div class="page-content-tab">

        <div class="container-fluid">
            <!-- Page-Title -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <div class="float-right">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Metrica</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Dashboard</h4>
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>
            <!-- end page title end breadcrumb -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0">Audience Overview</h4>
                            <div class="">
                                <div id="ana_dash_1" class="apex-charts"></div>
                            </div>
                        </div>
                        <!--end card-body-->
                        <div class="card-body bg-light-alt chart-report-card">
                            <div class="row d-flex justify-content-between">
                                <div class="col-lg-4">
                                    <div class="media">
                                        <div class="report-main-icon bg-card mr-2">
                                            <i data-feather="users" class="align-self-center icon-dual-2"></i>
                                        </div>
                                        <div class="media-body align-self-center text-truncate">
                                            <h4 class="mt-0 mb-0 font-weight-semibold text-dark font-24">140k
                                                <span class="text-success  font-12 font-weight-normal"><i
                                                        class="mdi mdi-arrow-up mr-1"></i>21%</span>
                                            </h4>
                                            <p class="text-dark font-weight-semibold mb-0 font-14">Users</p>
                                        </div>
                                        <!--end media-body-->
                                    </div>
                                    <!--end media-->
                                </div>
                                <!--end col-->
                                <div class="col-lg-4">
                                    <div class="media">
                                        <div class="report-main-icon bg-card mr-2">
                                            <i data-feather="eye" class="align-self-center icon-dual-2"></i>
                                        </div>
                                        <div class="media-body align-self-center text-truncate">
                                            <h4 class="mt-0 mb-0 font-weight-semibold text-dark font-24">2154
                                                <span class="text-success  font-12 font-weight-normal"><i
                                                        class="mdi mdi-arrow-up mr-1"></i>21%</span>
                                            </h4>
                                            <p class="text-dark font-weight-semibold mb-0 font-14">Page views</p>
                                        </div>
                                        <!--end media-body-->
                                    </div>
                                    <!--end media-->
                                </div>
                                <!--end col-->
                                <div class="col-lg-4">
                                    <div class="media">
                                        <div class="report-main-icon bg-card mr-2">
                                            <i data-feather="headphones" class="align-self-center icon-dual-2"></i>
                                        </div>
                                        <div class="media-body align-self-center text-truncate">
                                            <h4 class="mt-0 mb-0 font-weight-semibold text-dark font-24">183k
                                                <span class="text-success  font-12 font-weight-normal"><i
                                                        class="mdi mdi-arrow-up mr-1"></i>21%</span>
                                            </h4>
                                            <p class="text-dark font-weight-semibold mb-0 font-14">Impressions</p>
                                        </div>
                                        <!--end media-body-->
                                    </div>
                                    <!--end media-->
                                </div>
                                <!--end col-->
                            </div>
                            <!--end row-->
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0">Sessions Device</h4>
                            <div id="ana_device" class="apex-charts"></div>
                            <div class="table-responsive mt-4">
                                <table class="table mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Device</th>
                                            <th>Sassions</th>
                                            <th>Day</th>
                                            <th>Week</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">Dasktops</th>
                                            <td>1843</td>
                                            <td>-3</td>
                                            <td>-12</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Tablets</th>
                                            <td>2543</td>
                                            <td>-5</td>
                                            <td>-2</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Mobiles</th>
                                            <td>3654</td>
                                            <td>-5</td>
                                            <td>-6</td>
                                        </tr>

                                    </tbody>
                                </table>
                                <!--end /table-->
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <!--end row-->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <h4 class="header-title mt-0 mb-3">Live Visits Our New Site</h4>
                                    <div id="circlechart" class="apex-charts"></div>
                                </div>
                                <!--end col-->
                                <div class="col-lg-6">
                                    <h4 class="header-title mt-0 mb-3">Traffic Sources</h4>
                                    <div class="traffic-card">
                                        <h3>80</h3>
                                        <h5>Right Now</h5>
                                    </div>
                                    <div class="progress mb-4">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                            role="progressbar" style="width: 55%" aria-valuenow="55" aria-valuemin="0"
                                            aria-valuemax="100">55%</div>
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 28%"
                                            aria-valuenow="28" aria-valuemin="0" aria-valuemax="100">28%</div>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 17%"
                                            aria-valuenow="17" aria-valuemin="0" aria-valuemax="100">17%</div>
                                    </div>
                                    <ul class="list-unstyled url-list mb-0">
                                        <li>
                                            <i class="mdi mdi-circle-medium text-primary"></i>
                                            <span>Organic</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-circle-medium text-secondary"></i>
                                            <span>Direct</span>
                                        </li>
                                        <li>
                                            <i class="mdi mdi-circle-medium text-warning"></i>
                                            <span>Campaign</span>
                                        </li>
                                    </ul>
                                </div>
                                <!--end col-->
                            </div>
                            <!--end row-->
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0 mb-3">Organic Traffic in USA</h4>

                            <div class="row">
                                <div class="col-lg-7">
                                    <div id="usa" class="drop-shadow-map" style="height: 250px"></div>
                                </div>
                                <!--end col-->
                                <div class="col-lg-5 align-self-center">
                                    <div class="">
                                        <span class="text-dark">Texas</span>
                                        <small class="float-right text-muted ml-3 font-13">81%</small>
                                        <div class="progress mt-2" style="height:3px;">
                                            <div class="progress-bar bg-pink" role="progressbar"
                                                style="width: 81%; border-radius:5px;" aria-valuenow="81"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <span class="text-dark">Washington</span>
                                        <small class="float-right text-muted ml-3 font-13">68%</small>
                                        <div class="progress mt-2" style="height:3px;">
                                            <div class="progress-bar bg-secondary" role="progressbar"
                                                style="width: 68%; border-radius:5px;" aria-valuenow="68"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-dark">Wyoming</span>
                                        <small class="float-right text-muted ml-3 font-13">48%</small>
                                        <div class="progress mt-2" style="height:3px;">
                                            <div class="progress-bar bg-purple" role="progressbar"
                                                style="width: 48%; border-radius:5px;" aria-valuenow="48"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <span class="text-dark">Virginia</span>
                                        <small class="float-right text-muted ml-3 font-13">32%</small>
                                        <div class="progress mt-2" style="height:3px;">
                                            <div class="progress-bar bg-warning" role="progressbar"
                                                style="width: 32%; border-radius:5px;" aria-valuenow="32"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                <!--end col-->
                            </div>
                            <!--end row-->
                        </div>
                        <!--end card-body-->
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card">
                        <div class="card-body">
                            <div class="row d-flex justify-content-center">
                                <div class="col-8">
                                    <p class="text-dark font-weight-semibold font-14">Sessions</p>
                                    <h3 class="my-3">24k</h3>
                                    <p class="mb-0 text-truncate"><span class="text-success"><i
                                                class="mdi mdi-trending-up"></i>8.5%</span> New Sessions Today</p>
                                </div>
                                <div class="col-4 align-self-center">
                                    <div class="report-main-icon bg-light-alt">
                                        <i data-feather="users" class="align-self-center icon-dual-pink icon-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card">
                        <div class="card-body">
                            <div class="row d-flex justify-content-center">
                                <div class="col-8">
                                    <p class="text-dark font-weight-semibold font-14">Avg.Sessions</p>
                                    <h3 class="my-3">00:18</h3>
                                    <p class="mb-0 text-truncate"><span class="text-success"><i
                                                class="mdi mdi-trending-up"></i>1.5%</span> Weekly Avg.Sessions</p>
                                </div>
                                <div class="col-4 align-self-center">
                                    <div class="report-main-icon bg-light-alt">
                                        <i data-feather="clock"
                                            class="align-self-center icon-dual-secondary icon-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card">
                        <div class="card-body">
                            <div class="row d-flex justify-content-center">
                                <div class="col-8">
                                    <p class="text-dark font-weight-semibold font-14">Bounce Rate</p>
                                    <h3 class="my-3">$2400</h3>
                                    <p class="mb-0 text-truncate"><span class="text-danger"><i
                                                class="mdi mdi-trending-down"></i>35%</span> Bounce Rate Weekly</p>
                                </div>
                                <div class="col-4 align-self-center">
                                    <div class="report-main-icon bg-light-alt">
                                        <i data-feather="pie-chart"
                                            class="align-self-center icon-dual-purple icon-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card">
                        <div class="card-body">
                            <div class="row d-flex justify-content-center">
                                <div class="col-8">
                                    <p class="text-dark font-weight-semibold font-14">Goal Completions</p>
                                    <h3 class="my-3">85000</h3>
                                    <p class="mb-0 text-truncate"><span class="text-success"><i
                                                class="mdi mdi-trending-up"></i>10.5%</span> Completions Weekly</p>
                                </div>
                                <div class="col-4 align-self-center">
                                    <div class="report-main-icon bg-light-alt">
                                        <i data-feather="briefcase"
                                            class="align-self-center icon-dual-warning icon-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0 mb-3">Browser Used By Users</h4>
                            <div class="table-responsive browser_users">
                                <table class="table mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-top-0">Browser</th>
                                            <th class="border-top-0">Sessions</th>
                                            <th class="border-top-0">Bounce Rate</th>
                                            <th class="border-top-0">Transactions</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><i class="fab fa-chrome mr-2 text-danger font-16"></i>Chrome</td>
                                            <td>10853<small class="text-muted">(52%)</small></td>
                                            <td> 52.80%</td>
                                            <td>566<small class="text-muted">(92%)</small></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><i class="fab fa-safari mr-2 text-info font-16"></i>Safari</td>
                                            <td>2545<small class="text-muted">(47%)</small></td>
                                            <td> 47.54%</td>
                                            <td>498<small class="text-muted">(81%)</small></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><i
                                                    class="fab fa-internet-explorer mr-2 text-warning font-16"></i>Internet-Explorer
                                            </td>
                                            <td>1836<small class="text-muted">(38%)</small></td>
                                            <td> 41.12%</td>
                                            <td>455<small class="text-muted">(74%)</small></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><i class="fab fa-opera mr-2 text-danger font-16"></i>Opera</td>
                                            <td>1958<small class="text-muted">(31%)</small></td>
                                            <td> 36.82%</td>
                                            <td>361<small class="text-muted">(61%)</small></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><i class="fab fa-firefox mr-2 text-blue font-16"></i>Firefox</td>
                                            <td>1566<small class="text-muted">(26%)</small></td>
                                            <td> 29.33%</td>
                                            <td>299<small class="text-muted">(49%)</small></td>
                                        </tr>
                                        <!--end tr-->
                                    </tbody>
                                </table>
                                <!--end table-->
                            </div>
                            <!--end /div-->
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0 mb-3">Traffic Sources</h4>
                            <div class="table-responsive browser_users">
                                <table class="table mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-top-0">Channel</th>
                                            <th class="border-top-0">Sessions</th>
                                            <th class="border-top-0">Prev.Period</th>
                                            <th class="border-top-0">% Change</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><a href="" class="text-primary">Organic search</a></td>
                                            <td>10853<small class="text-muted">(52%)</small></td>
                                            <td>566<small class="text-muted">(92%)</small></td>
                                            <td> 52.80% <i class="fas fa-caret-up text-success font-16"></i></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><a href="" class="text-primary">Direct</a></td>
                                            <td>2545<small class="text-muted">(47%)</small></td>
                                            <td>498<small class="text-muted">(81%)</small></td>
                                            <td> -17.20% <i class="fas fa-caret-down text-danger font-16"></i></td>

                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><a href="" class="text-primary">Referal</a></td>
                                            <td>1836<small class="text-muted">(38%)</small></td>
                                            <td>455<small class="text-muted">(74%)</small></td>
                                            <td> 41.12% <i class="fas fa-caret-up text-success font-16"></i></td>

                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><a href="" class="text-primary">Email</a></td>
                                            <td>1958<small class="text-muted">(31%)</small></td>
                                            <td>361<small class="text-muted">(61%)</small></td>
                                            <td> -8.24% <i class="fas fa-caret-down text-danger font-16"></i></td>
                                        </tr>
                                        <!--end tr-->
                                        <tr>
                                            <td><a href="" class="text-primary">Social</a></td>
                                            <td>1566<small class="text-muted">(26%)</small></td>
                                            <td>299<small class="text-muted">(49%)</small></td>
                                            <td> 29.33% <i class="fas fa-caret-up text-success"></i></td>
                                        </tr>
                                        <!--end tr-->
                                    </tbody>
                                </table>
                                <!--end table-->
                            </div>
                            <!--end /div-->
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

        </div><!-- container -->

        <!--  Modal content for the above example -->
        <div class="modal modal-rightbar fade" tabindex="-1" role="dialog" aria-labelledby="MetricaRightbar"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="MetricaRightbar">Appearance</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary btn-circle btn-square"
                            data-dismiss="modal" aria-hidden="true"><i class="mdi mdi-close"></i></button>
                    </div>
                    <div class="modal-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-pills nav-justified mt-2 mb-4" role="tablist">
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link active" data-toggle="tab" href="#ActivityTab" role="tab">Activity</a>
                            </li>
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link" data-toggle="tab" href="#TasksTab" role="tab">Tasks</a>
                            </li>
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link" data-toggle="tab" href="#SettingsTab" role="tab">Settings</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane active " id="ActivityTab" role="tabpanel">
                                <div class="bg-light mx-n3">
                                    <img src="../assets/images/small/img-1.gif" alt="" class="d-block mx-auto my-4"
                                        height="180">
                                </div>
                                <div class="slimscroll scroll-rightbar">
                                    <div class="activity">
                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-checkbox-marked-circle-outline bg-soft-success"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">10 Min ago</small>
                                                    <a href="#" class="m-0 w-75">Task finished</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                            </div>
                                        </div>

                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-timer-off bg-soft-pink"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">50 Min ago</small>
                                                    <a href="#" class="m-0 w-75">Task Overdue</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                                <span class="badge badge-soft-secondary">Design</span>
                                                <span class="badge badge-soft-secondary">HTML</span>
                                            </div>
                                        </div>
                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-alert-decagram bg-soft-purple"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">10 hours ago</small>
                                                    <a href="#" class="m-0 w-75">New Task</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                            </div>
                                        </div>

                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-clipboard-alert bg-soft-warning"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">yesterday</small>
                                                    <a href="#" class="m-0 w-75">New Comment</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                            </div>
                                        </div>
                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-clipboard-alert bg-soft-secondary"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">01 feb 2020</small>
                                                    <a href="#" class="m-0 w-75">New Lead Meting</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                            </div>
                                        </div>
                                        <div class="activity-info">
                                            <div class="icon-info-activity">
                                                <i class="mdi mdi-checkbox-marked-circle-outline bg-soft-success"></i>
                                            </div>
                                            <div class="activity-info-text mb-2">
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">26 jan 2020</small>
                                                    <a href="#" class="m-0 w-75">Task finished</a>
                                                </div>
                                                <p class="text-muted mb-2 text-truncate">There are many variations of
                                                    passages.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end activity-->
                                </div>
                                <!--end activity-scroll-->
                            </div>
                            <!--end tab-pane-->
                            <div class="tab-pane" id="TasksTab" role="tabpanel">
                                <div class="m-0">
                                    <div id="rightbar_chart" class="apex-charts"></div>
                                </div>
                                <div class="text-center mt-n2 mb-2">
                                    <button type="button" class="btn btn-soft-primary">Create Project</button>
                                    <button type="button" class="btn btn-soft-primary">Create Task</button>
                                </div>
                                <div class="slimscroll scroll-rightbar">
                                    <div class="p-2">
                                        <div class="media mb-3">
                                            <img src="../assets/images/widgets/project3.jpg" alt=""
                                                class="thumb-lg rounded-circle">
                                            <div class="media-body align-self-center text-truncate ml-3">
                                                <p class="text-success font-weight-semibold mb-0 font-14">Project</p>
                                                <h4 class="mt-0 mb-0 font-weight-semibold text-dark font-18">Payment App
                                                </h4>
                                            </div>
                                            <!--end media-body-->
                                        </div>
                                        <span><b>Deadline:</b> 02 June 2020</span>
                                        <a href="javascript: void(0);" class="d-block mt-3">
                                            <p class="text-muted mb-0">Complete Tasks<span
                                                    class="float-right">75%</span></p>
                                            <div class="progress mt-2" style="height: 4px;">
                                                <div class="progress-bar bg-secondary" role="progressbar"
                                                    style="width: 75%" aria-valuenow="75" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </a>
                                    </div>
                                    <hr class="hr-dashed">
                                </div>
                            </div>
                            <!--end tab-pane-->
                            <div class="tab-pane" id="SettingsTab" role="tabpanel">
                                <div class="p-1 bg-light mx-n3">
                                    <h6 class="px-3">Account Settings</h6>
                                </div>
                                <div class="p-2 text-left mt-3">
                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch1"
                                            checked="">
                                        <label class="custom-control-label" for="settings-switch1">Auto updates</label>
                                    </div>

                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch2">
                                        <label class="custom-control-label" for="settings-switch2">Location
                                            Permission</label>
                                    </div>

                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch3"
                                            checked="">
                                        <label class="custom-control-label" for="settings-switch3">Show offline
                                            Contacts</label>
                                    </div>
                                </div>
                                <div class="p-1 bg-light mx-n3">
                                    <h6 class="px-3">General Settings</h6>
                                </div>
                                <div class="p-2 text-left mt-3">
                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch4"
                                            checked="">
                                        <label class="custom-control-label" for="settings-switch4">Show me
                                            Online</label>
                                    </div>

                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch5">
                                        <label class="custom-control-label" for="settings-switch5">Status visible to
                                            all</label>
                                    </div>

                                    <div class="custom-control custom-switch switch-primary mb-3">
                                        <input type="checkbox" class="custom-control-input" id="settings-switch6"
                                            checked="">
                                        <label class="custom-control-label" for="settings-switch6">Notifications
                                            Popup</label>
                                    </div>
                                </div>
                            </div>
                            <!--end tab-pane-->
                        </div>
                        <!--end tab-content-->
                    </div>
                    <!--end modal-body-->
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

       
    </div>

<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>

<script src="<?php echo base_url(); ?>plugins/apexcharts/apexcharts.min.js"></script>
<script src="<?php echo base_url(); ?>assets/pages/jquery.analytics_dashboard.init.js"></script>