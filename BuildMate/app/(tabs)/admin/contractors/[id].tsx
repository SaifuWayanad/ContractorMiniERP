import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type ContractorDetail = {
  id: number;
  company_name: string;
  contact_person: string | null;
  email: string | null;
  phone: string | null;
  alternate_phone: string | null;
  city: string | null;
  state: string | null;
  status: string;
  projects_count: number;
  users: Array<{ id: number; name: string; email: string | null; role: string | null; status: string }>;
};

export default function ContractorDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [contractor, setContractor] = useState<ContractorDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [toggling, setToggling] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get(`/mobile/admin/contractors/${id}`);
      setContractor(res.data);
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  async function handleToggleStatus() {
    if (!contractor) return;
    const next = contractor.status === 'active' ? 'inactive' : 'active';
    Alert.alert(
      'Change Status',
      `Mark this contractor as ${next}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Confirm',
          onPress: async () => {
            setToggling(true);
            try {
              const res = await api.patch(`/mobile/admin/contractors/${id}/status`);
              setContractor((prev) => prev ? { ...prev, status: res.data.status } : prev);
            } catch (err: any) {
              Alert.alert('Error', err?.response?.data?.message ?? 'Failed to update status.');
            } finally { setToggling(false); }
          },
        },
      ]
    );
  }

  if (loading) return <View style={s.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  if (!contractor) return <View style={s.centered}><Text style={{ color: Colors.textMuted }}>Contractor not found.</Text></View>;

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScreenHeader
        title={contractor.company_name}
        subtitle="Contractor Details"
        right={
          <TouchableOpacity
            style={s.editBtn}
            onPress={() => router.push({ pathname: '/(tabs)/admin/contractors/form', params: { id: String(contractor.id) } })}
          >
            <Ionicons name="create-outline" size={20} color={Colors.primary} />
          </TouchableOpacity>
        }
      />
      <ScrollView contentContainerStyle={s.content} showsVerticalScrollIndicator={false}>
        <Card title="Info">
          {contractor.contact_person && <View style={s.row}><Text style={s.label}>Contact</Text><Text style={s.value}>{contractor.contact_person}</Text></View>}
          {contractor.email && <View style={s.row}><Text style={s.label}>Email</Text><Text style={s.value}>{contractor.email}</Text></View>}
          {contractor.phone && <View style={s.row}><Text style={s.label}>Phone</Text><Text style={s.value}>{contractor.phone}</Text></View>}
          {contractor.alternate_phone && <View style={s.row}><Text style={s.label}>Alt. Phone</Text><Text style={s.value}>{contractor.alternate_phone}</Text></View>}
          {(contractor.city || contractor.state) && (
            <View style={s.row}>
              <Text style={s.label}>Location</Text>
              <Text style={s.value}>{[contractor.city, contractor.state].filter(Boolean).join(', ')}</Text>
            </View>
          )}
          <View style={s.row}><Text style={s.label}>Projects</Text><Text style={s.value}>{contractor.projects_count}</Text></View>
          <View style={[s.row, { borderBottomWidth: 0 }]}>
            <Text style={s.label}>Status</Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
              <Badge status={contractor.status} />
              <TouchableOpacity onPress={handleToggleStatus} disabled={toggling} style={s.toggleBtn}>
                {toggling
                  ? <ActivityIndicator size="small" color={Colors.white} />
                  : <Text style={s.toggleBtnText}>{contractor.status === 'active' ? 'Deactivate' : 'Activate'}</Text>
                }
              </TouchableOpacity>
            </View>
          </View>
        </Card>

        {contractor.users.length > 0 && (
          <Card title={`Users (${contractor.users.length})`}>
            {contractor.users.map((u, i) => (
              <View key={u.id} style={[s.userRow, i > 0 && s.border]}>
                <View style={s.userAvatar}>
                  <Text style={s.userAvatarText}>{u.name.charAt(0).toUpperCase()}</Text>
                </View>
                <View style={{ flex: 1, marginLeft: 10 }}>
                  <Text style={s.userName}>{u.name}</Text>
                  {u.email && <Text style={s.userEmail}>{u.email}</Text>}
                  {u.role && <Text style={s.userRole}>{u.role}</Text>}
                </View>
                <Badge status={u.status} />
              </View>
            ))}
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  editBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.accent,
    alignItems: 'center', justifyContent: 'center',
  },
  row: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  label: { fontSize: 13, color: Colors.textSecondary },
  value: { fontSize: 13, fontWeight: '600', color: Colors.text },
  toggleBtn: {
    backgroundColor: Colors.primary, borderRadius: 6, paddingHorizontal: 10, paddingVertical: 5, minWidth: 80, alignItems: 'center',
  },
  toggleBtnText: { fontSize: 12, fontWeight: '700', color: Colors.white },
  userRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10 },
  border: { borderTopWidth: 1, borderTopColor: Colors.border },
  userAvatar: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.primary, alignItems: 'center', justifyContent: 'center',
  },
  userAvatarText: { fontSize: 14, fontWeight: '800', color: Colors.white },
  userName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  userEmail: { fontSize: 12, color: Colors.textSecondary, marginTop: 1 },
  userRole: { fontSize: 12, color: Colors.textMuted, marginTop: 1 },
});
