<?

/**
 * Wrapper for the xhprof execution profiler php extension.
 *
 * 2013-02-23 - AR.
 */

class Quick_Data_Xhprof
{
    protected $_profile_data = array();
    protected $_columns = array();
    protected $_profile_time_columns = 'fract_elapsed,elapsed_cum,elapsed_self,count,cpu_self,mem_self,name';
    protected $_profile_memory_columns = 'fract_elapsed,elapsed_cum,elapsed_self,count,cpu_self,mem_self,name';
    protected $_sortby = 'time';

    const PROF_NO_BUILTINS = XHPROF_FLAGS_NO_BUILTINS;
    const PROF_CPU = XHPROF_FLAGS_CPU;
    const PROF_MEMORY = XHPROF_FLAGS_MEMORY;

    public function __construct( ) {
        if (!function_exists('xhprof_enable'))
            throw new Exception("xhprof: extension not installed");
        $this->profileTime();
    }

    static public function create( ) {
        return new self;
    }

    public function profileTime( ) {
        $this->_columns = explode(",", $this->_profile_time_columns);
        $this->_sortby = 'time';
    }

    public function profileMemory( ) {
        $this->_columns = explode(",", $this->_profile_memory_columns);
        $this->_sortby = 'memory';
    }

    public function showColumns( $column_csv ) {
        if ($columns_csv)
            $this->_columns = explode(",", $columns_csv);
        else
            $this->_columns = explode(",", $this->_default_columns);
    }

    public function runScript( $argv ) {
        if ($argv)
            include $argv[0];
    }

    public function startProfiling( $flags = null ) {
        if ($flags === null) $flags = XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY;
        xhprof_enable($flags);
        return $this;
    }

    public function stopProfiling( ) {
        $data = xhprof_disable();
        if ($data) $this->_profile_data = $data;
        return $this;
    }

    public function samplingEnable( ) {
        xhprof_sampling_enable();
        return $this;
    }

    public function samplingDisable( ) {
        xhprof_sampling_disable();
        return $this;
    }

    public function getRawData( ) {
        return $this->_profile_data;
    }

    public function getProfile( ) {
        $d = $this->_findExclusiveProfile();
        $d = $this->_findDisplayStats($d);
        $d = $this->_formatDisplayStats($d, $this->_columns);
        $d = $this->_assembleDisplayStats($d);
        return $d;
    }


    protected function _findExclusiveProfile( ) {
        $d = array();
        foreach ($this->_profile_data as $fn => $info) {
            list($caller, $callee) = explode('==>', $fn);
            list($caller, $caller_depth) = explode('@', $caller);
            list($callee, $callee_depth) = explode('@', $callee);
            if (!$callee) { $child = $caller; $parent = ''; }
            else { $parent = $caller; $child = $callee; }

            if (!isset($d[$child])) $d[$child] = $this->_zeroDefaults();
            if ($parent && !isset($d[$parent])) $d[$parent] = $this->_zeroDefaults();

            $d[$child]['ct'] += $info['ct'];    // count of calls

            // inclusive counts are total for the called process including all its nested calls
            $d[$child]['wt'] += $info['wt'];    // elapsed time
            $d[$child]['cpu'] += $info['cpu'];  // ? cpu time ?
            $d[$child]['mu'] += $info['mu'];    // memory use
            $d[$child]['pmu'] += $info['pmu'];  // peak memory use

            // exclusive counts are just for the called process, not including nested calls
            // anything expended in the child (callee) was not done by the parent (caller)
            $d[$child]['wtx'] += $info['wt'];  // elapsed time, exclusive
            $d[$child]['cpux'] += $info['cpu'];
            $d[$child]['mux'] += $info['mu'];
            $d[$child]['pmux'] += $info['pmu'];
            if ($parent) {
                $d[$parent]['wtx'] -= $info['wt'];
                $d[$parent]['cpux'] -= $info['cpu'];
                $d[$parent]['mux'] -= $info['mu'];
                $d[$parent]['pmux'] += $info['pmu'];
            }
        }
        return $d;
    }

    protected function _findDisplayStats( $data ) {
        $ret = array();
        foreach ($data as $d) {
            $tot_elapsed += $d['wtx'];
            $tot_cpu += $d['cpux'];
            $tot_memory += $d['mux'] > 0 ? $d['mux'] : 0;               // report % of mem allocated, ie > 0 chunks
        }
        foreach ($data as $fn => $d) {
            $r['name'] = $fn;
            $r['count'] = $d['ct'];

            $r['fract_elapsed'] = $d['wtx'] / $tot_elapsed;
            $r['fract_cpu'] = $d['cpux'] / $tot_cpu;
            $r['fract_memory'] = $d['mux'] / $tot_memory;

            $r['elapsed_self'] = $d['wtx'] / 1000000.0;
            $r['elapsed_self_ea'] = $d['wt'] / $d['ct'] / 1000000;
            $r['elapsed_tot'] = $d['wt'] / 1000000.0;

            $r['cpu_self'] = $d['cpux'] / 1000000.0;
            $r['cpu_self_ea'] = $d['cpux'] / $d['ct'] / 1000000.0;
            $r['cpu_tot'] = $d['cpu'] / 1000000.0;

            $r['mem_self'] = $d['mux'];
            $r['mem_self_ea'] = $d['mux'] / $d['ct'];
            $r['mem_tot'] = $d['mu'];
            // memory
            // memory_peak
            $ret[] = $r;
        }
        return $ret;
    }

    protected function _formatDisplayStats( $data, $columns ) {
        /*
         * The format of bsd prof output is
         * http://sourceware.org/binutils/docs/gprof/Flat-Profile.html#Flat-Profile
         *
         *     %   cumulative   self              self     total
         *    time   seconds   seconds    calls  ms/call  ms/call  name
         *    33.34      0.02     0.02     7208     0.00     0.00  open
         *    16.67      0.03     0.01      244     0.04     0.12  offtime
         *    16.67      0.04     0.01        8     1.25     1.25  memccpy
         *    16.67      0.05     0.01        7     1.43     1.43  write
         *    16.67      0.06     0.01                             mcount
         *     0.00      0.06     0.00      236     0.00     0.00  tzset
         *     0.00      0.06     0.00      192     0.00     0.00  tolower
         *   ...
         */

        $column_labels = array(
            'name' => '-Function',
            'count' => 'calls',

            'fract_elapsed' => 'Time%',
            'fract_cpu' => 'Cpu%',
            'fract_memory' => 'Mem%',

            'elapsed_self' => 'Time.s',
            'elapsed_tot' => 'time.T',
            'elapsed_cum' => 'Cum.s',

            'cpu_self' => 'Cpu.ms',
            'cpu_self_ea' => 'Cpu.ms',
            'cpu_tot' => 'cpu.T',

            'mem_self' => 'Mem',
            'mem_self_ea' => 'Mem.ea',
            'mem_tot' => 'Mem.T',
            'mem_cum' => 'Mem.C',
        );

        // canonical unix prof sorts by descending per-function elapsed time
        usort($data, create_function('$d1, $d2', 'return ($d1["elapsed_self"] - $d2["elapsed_self"]) < 0 ? 1 : -1;'));

        foreach ($data as $d) {
            $max_cpu_self = max($max_fract_cpu, $d['cpu_self']);
        }
        $cpu_precision = ($max_cpu_self > 100 ? 2 : 3);

        $ret = array();
        $lines = array();
        $fields = array();
        $cum_elapsed = 0;
        $cum_memory = 0;

        foreach ($columns as $column_name)
            $fields[] = $column_labels[$column_name];
        $lines[] = $fields;

        foreach ($data as $d) {
            $fields = array();
            foreach ($columns as $col) {
                switch ($col) {
                case 'name':
                    $fields[] = sprintf("-%s", $d['name']);
                    break;
                case 'count':
                    $fields[] = sprintf("%d", $d['count']);
                    break;

                case 'fract_elapsed':
                    $fields[] = sprintf("%5.2f%%", $d['fract_elapsed'] * 100);
                    break;
                case 'fract_cpu':
                    $fields[] = sprintf("%5.2f%%", $d['fract_cpu'] * 100);
                    break;
                case 'fract_memory':
                    $fields[] = sprintf("%5.2f%%", $d['fract_memory']);
                    break;

                case 'elapsed_self':
                    $fields[] = sprintf("%6.3f", $d['elapsed_self']);
                    break;
                case 'elapsed_tot':
                    $fields[] = sprintf("%6.2f", $d['elapsed_tot']);
                    break;
                case 'elapsed_cum':
                    $cum_elapsed += $d['elapsed_self'];
                    $fields[] = sprintf("%5.3f", $cum_elapsed);
                    break;

                case 'cpu_self':
                    $fields[] = sprintf("%.{$cpu_precision}f", $d['cpu_self'] * 1000);
                    break;
                case 'cpu_self_ea':
                    $fields[] = sprintf("%.2{$cpu_precision}f", $d['cpu_self_ea'] * 1000);
                    break;
                case 'cpu_tot':
                    $fields[] = sprintf("%6.4{$cpu_precision}f", $d['cpu_self']);
                    break;

                case 'mem_self':
                    $fields[] = sprintf("%d", $d['mem_self']);
                    break;
                case 'mem_self_ea':
                    $fields[] = sprintf("%d", $d['mem_self_ea']);
                    break;
                case 'mem_cum':
                    $cum_memory += $d['mem_self'];
                    $fields[] = sprintf("%d", $cum_memory);
                    break;

                default:
                    $fields[] = "(other)";
                    break;
                }
            }
            $lines[] = $fields;
        }
        return $lines;
    }

    protected function _assembleDisplayStats( $data ) {
        foreach ($data as $row => $rowdata) {
            foreach ($rowdata as $col => $value) {
                $widths[$col] = max(strlen($value), $widths[$col]);
            }
        }
        foreach ($data as $row => $rowdata) {
            $vals = array();
            foreach ($rowdata as $col => $value) {
                if (is_numeric($value))
                    $vals[] = sprintf("%{$widths[$col]}s", $value);
                elseif ($value[0] === '-')
                    $vals[] = sprintf("%-{$widths[$col]}s", substr($value, 1));
                else
                    $vals[] = sprintf("%{$widths[$col]}s", $value);
            }
            $lines[] = implode("  ", $vals) . "\n";
        }
        return $lines;
    }

    protected function _zeroDefaults( ) {
        return array(
            'ct' => 0,
            'wt' => 0,
            'cpu' => 0,
            'mu' => 0,
            'pmu' => 0,
            'wtx' => 0,
            'cpux' => 0,
            'mux' => 0,
            'pmux' => 0,
        );
    }
}
