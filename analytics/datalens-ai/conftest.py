"""Make the service modules importable from any pytest invocation directory."""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
